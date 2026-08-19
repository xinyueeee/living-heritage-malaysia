#!/usr/bin/env python3
"""Batch-extract Tourism Malaysia experience pages for manual review.

This utility deliberately stops at raw.json and review.csv. It never inserts,
updates, or deletes application data.
"""

from __future__ import annotations

import argparse
import base64
import csv
import hashlib
import json
import re
import subprocess
import sys
import time
import unicodedata
from dataclasses import dataclass
from datetime import datetime, timezone
from email.utils import parsedate_to_datetime
from pathlib import Path
from typing import Any, Callable, Iterable
from urllib.parse import urljoin, urlsplit, urlunsplit

import requests
from bs4 import BeautifulSoup, Tag


TEST_URL = "https://www.malaysia.travel/explore/kampung-sungai-haji-dorani"
USER_AGENT = (
    "LivingHeritageMalaysia-ReviewScraper/2.0 "
    "(+manual-review; educational project)"
)
TEMPORARY_STATUS_CODES = {429, 500, 502, 503, 504}
DEFAULT_DELAY_SECONDS = 3.0

CULTURAL_EXPERIENCE_TYPE = {"id": 1, "name": "Cultural Experience"}
CATEGORY_RULES = {
    1: {
        "name": "Workshop",
        "signals": (
            "workshop",
            "class",
            "hands on",
            "learn to make",
            "lesson",
            "demonstration",
        ),
    },
    2: {
        "name": "Culinary",
        "signals": (
            "culinary",
            "cooking",
            "food",
            "cuisine",
            "gastronomy",
            "dish",
            "delicacy",
        ),
    },
    3: {
        "name": "Heritage",
        "signals": (
            "culture heritage",
            "cultural heritage",
            "heritage",
            "historical",
            "history",
            "museum",
            "mansion",
            "temple",
            "landmark",
            "traditional village",
            "cultural village",
            "homestay",
        ),
    },
    4: {
        "name": "Adventure",
        "signals": (
            "nature adventure",
            "adventure",
            "hiking",
            "trekking",
            "trail",
            "climb",
            "cave",
            "outdoor",
        ),
    },
    5: {
        "name": "Wildlife",
        "signals": (
            "wildlife",
            "animal",
            "birdwatching",
            "sanctuary",
            "orangutan",
            "conservation",
        ),
    },
    6: {
        "name": "Arts & Crafts",
        "signals": (
            "arts crafts",
            "art craft",
            "handicraft",
            "craft",
            "batik",
            "weaving",
            "pottery",
            "embroidery",
        ),
    },
}

MALAYSIAN_STATES = (
    "Negeri Sembilan",
    "Kuala Lumpur",
    "Pulau Pinang",
    "Labuan",
    "Putrajaya",
    "Johor",
    "Kedah",
    "Kelantan",
    "Melaka",
    "Pahang",
    "Perak",
    "Perlis",
    "Sabah",
    "Sarawak",
    "Selangor",
    "Terengganu",
)
STATE_ALIASES = {
    "penang": "Pulau Pinang",
    "pulau pinang": "Pulau Pinang",
    "wp kuala lumpur": "Kuala Lumpur",
    "wilayah persekutuan kuala lumpur": "Kuala Lumpur",
    "wp labuan": "Labuan",
    "wp putrajaya": "Putrajaya",
}

REVIEW_COLUMNS = [
    "source_url",
    "normalized_source_url",
    "scrape_status",
    "scrape_warning",
    "experiences_name",
    "short_description",
    "description",
    "description_provenance",
    "location_name",
    "source_state",
    "price",
    "duration",
    "start_date",
    "end_date",
    "operating_hours",
    "candidate_type_id",
    "candidate_type_name",
    "candidate_category_id",
    "candidate_category_name",
    "category_confidence",
    "category_review_required",
    "latitude",
    "longitude",
    "candidate_contact_number",
    "contact_review_required",
    "source_image_url",
    "image_review_required",
    "candidate_status",
    "provider",
    "source_email",
    "activities_summary",
    "transportation_summary",
    "normalized_title",
    "normalized_location",
    "source_content_hash",
    "generated_duplicate_possible",
    "generated_match_note",
    "database_duplicate_possible",
    "database_match_note",
    "review_status",
    "review_notes",
]


class ScrapeError(RuntimeError):
    """A classified error that can be safely recorded in batch output."""

    def __init__(
        self,
        error_type: str,
        message: str,
        http_status: int | None = None,
    ) -> None:
        super().__init__(message)
        self.error_type = error_type
        self.http_status = http_status


@dataclass(frozen=True)
class FetchResult:
    html: str
    status_code: int
    content_type: str | None
    rate_limit: str | None
    rate_remaining: str | None


@dataclass
class BatchResult:
    received_count: int
    records: list[dict[str, Any]]
    review_rows: list[dict[str, Any]]
    errors: list[dict[str, Any]]
    duplicate_inputs: list[dict[str, str]]


def normalize_text(value: str | None) -> str | None:
    """Collapse source whitespace without changing its wording."""
    if value is None:
        return None

    normalized = unicodedata.normalize("NFKC", value).replace("\u00a0", " ")
    normalized = re.sub(r"\s+", " ", normalized).strip()
    normalized = re.sub(r"\s+([,.;:!?])", r"\1", normalized)
    return normalized or None


def normalize_for_matching(value: str | None) -> str | None:
    """Create a stable comparison value for titles and locations."""
    text = normalize_text(value)
    if text is None:
        return None

    text = text.casefold()
    text = re.sub(r"[^\w]+", " ", text, flags=re.UNICODE)
    return re.sub(r"\s+", " ", text).strip() or None


def normalize_title(value: str | None) -> str | None:
    return normalize_for_matching(value)


def normalize_location(value: str | None) -> str | None:
    return normalize_for_matching(value)


def normalize_canonical_url(value: str) -> str:
    """Validate the host and remove query/fragment data used for tracking."""
    parts = urlsplit(value.strip())
    if parts.scheme.lower() not in {"http", "https"} or not parts.netloc:
        raise ScrapeError("invalid_url", f"Invalid source URL: {value}")

    hostname = (parts.hostname or "").lower()
    if hostname != "malaysia.travel" and not hostname.endswith(".malaysia.travel"):
        raise ScrapeError(
            "invalid_url",
            f"Refusing non-Tourism Malaysia URL: {value}",
        )

    port = parts.port
    netloc = hostname
    if port and not (
        (parts.scheme.lower() == "https" and port == 443)
        or (parts.scheme.lower() == "http" and port == 80)
    ):
        netloc = f"{hostname}:{port}"

    path = re.sub(r"/{2,}", "/", parts.path or "/")
    if path != "/":
        path = path.rstrip("/")

    return urlunsplit((parts.scheme.lower(), netloc, path, "", ""))


def normalize_phone_number(value: str) -> str | None:
    """Remove display punctuation without guessing a country code."""
    text = normalize_text(value)
    if text is None:
        return None

    digits = re.sub(r"\D", "", text)
    if not digits:
        return None
    return f"+{digits}" if text.startswith("+") else digits


def resolve_relative_url(value: str | None, base_url: str) -> str | None:
    text = normalize_text(value)
    return urljoin(base_url, text) if text else None


def retry_delay(response: requests.Response | None, attempt: int) -> float:
    """Use Retry-After when valid; otherwise apply exponential backoff."""
    if response is not None:
        retry_after = response.headers.get("Retry-After")
        if retry_after:
            if retry_after.isdigit():
                return min(float(retry_after), 60.0)
            try:
                retry_at = parsedate_to_datetime(retry_after)
                if retry_at.tzinfo is None:
                    retry_at = retry_at.replace(tzinfo=timezone.utc)
                return min(
                    max(
                        (retry_at - datetime.now(timezone.utc)).total_seconds(),
                        0.0,
                    ),
                    60.0,
                )
            except (TypeError, ValueError, OverflowError):
                pass

    return float(2 ** (attempt - 1))


def fetch_page(
    session: requests.Session,
    url: str,
    timeout: float = 25.0,
    max_attempts: int = 3,
) -> FetchResult:
    """Fetch one page without attempting to evade access controls."""
    safe_url = normalize_canonical_url(url)
    last_error: Exception | None = None

    for attempt in range(1, max_attempts + 1):
        response: requests.Response | None = None
        try:
            response = session.get(safe_url, timeout=timeout, allow_redirects=True)

            title_match = re.search(
                r"<title[^>]*>(.*?)</title>",
                response.text,
                re.IGNORECASE | re.DOTALL,
            )
            response_title = (
                normalize_text(title_match.group(1)) if title_match else None
            )
            challenged = (
                response.headers.get("cf-mitigated") == "challenge"
                or (response_title or "").casefold() == "just a moment..."
            )
            if challenged:
                raise ScrapeError(
                    "cloudflare_challenge",
                    "A Cloudflare challenge was returned; no bypass was attempted.",
                    response.status_code,
                )

            if response.status_code == 403:
                raise ScrapeError(
                    "http_403",
                    "Tourism Malaysia returned HTTP 403; no bypass was attempted.",
                    403,
                )
            if response.status_code == 404:
                raise ScrapeError(
                    "http_404",
                    "Tourism Malaysia page was not found.",
                    404,
                )

            if response.status_code in TEMPORARY_STATUS_CODES:
                if attempt < max_attempts:
                    time.sleep(retry_delay(response, attempt))
                    continue
                error_type = (
                    "rate_limit_429"
                    if response.status_code == 429
                    else "temporary_http_error"
                )
                raise ScrapeError(
                    error_type,
                    f"Temporary HTTP {response.status_code} persisted after retries.",
                    response.status_code,
                )

            if response.status_code >= 400:
                raise ScrapeError(
                    "http_error",
                    f"Tourism Malaysia returned HTTP {response.status_code}.",
                    response.status_code,
                )

            content_type = response.headers.get("Content-Type")
            if content_type and "html" not in content_type.lower():
                raise ScrapeError(
                    "unexpected_content_type",
                    f"Expected HTML but received {content_type}.",
                    response.status_code,
                )

            return FetchResult(
                html=response.text,
                status_code=response.status_code,
                content_type=content_type,
                rate_limit=response.headers.get("X-RateLimit-Limit"),
                rate_remaining=response.headers.get("X-RateLimit-Remaining"),
            )
        except ScrapeError:
            raise
        except requests.Timeout as error:
            last_error = error
            if attempt < max_attempts:
                time.sleep(retry_delay(response, attempt))
                continue
            raise ScrapeError(
                "network_timeout",
                f"Request timed out after {max_attempts} attempts.",
            ) from error
        except requests.ConnectionError as error:
            last_error = error
            if attempt < max_attempts:
                time.sleep(retry_delay(response, attempt))
                continue
            raise ScrapeError(
                "network_error",
                f"Network connection failed after {max_attempts} attempts.",
            ) from error
        except requests.RequestException as error:
            raise ScrapeError(
                "network_error",
                f"HTTP request failed for {safe_url}: {error}",
            ) from error

    raise ScrapeError(
        "network_error",
        f"Could not fetch {safe_url}: {last_error}",
    )


def meta_content(
    soup: BeautifulSoup,
    *,
    name: str | None = None,
    property_name: str | None = None,
) -> str | None:
    selector = (
        f'meta[name="{name}"]'
        if name
        else f'meta[property="{property_name}"]'
    )
    node = soup.select_one(selector)
    return normalize_text(node.get("content")) if node else None


def match_state(value: str | None) -> str | None:
    normalized = normalize_for_matching(value)
    if not normalized:
        return None

    for alias, state in STATE_ALIASES.items():
        if re.search(rf"\b{re.escape(alias)}\b", normalized):
            return state
    for state in sorted(MALAYSIAN_STATES, key=len, reverse=True):
        state_key = normalize_for_matching(state)
        if state_key and re.search(rf"\b{re.escape(state_key)}\b", normalized):
            return state
    return None


def extract_page_state(soup: BeautifulSoup) -> str | None:
    """Prefer the page's related-state area over incidental article mentions."""
    selectors = (
        ".experience_section .experience_detail h2",
        ".experience_detail h2",
        'a[href*="/explore?state="]',
    )
    for selector in selectors:
        for node in soup.select(selector):
            state = match_state(node.get_text(" ", strip=True))
            if state:
                return state
    return None


def extract_metadata(soup: BeautifulSoup, source_url: str) -> dict[str, Any]:
    canonical_node = soup.select_one('link[rel="canonical"][href]')
    canonical_url = (
        resolve_relative_url(canonical_node.get("href"), source_url)
        if canonical_node
        else source_url
    )
    canonical_url = normalize_canonical_url(canonical_url or source_url)

    visible_title_node = soup.select_one(
        ".experience_detail_page section.py-4.bg-F7F7F7 h2"
    )
    if visible_title_node is None:
        visible_title_node = soup.select_one(".experience_detail_page h1")
    if visible_title_node is None:
        visible_title_node = soup.select_one(".experience_detail_page h2")

    source_categories: list[str] = []
    for node in soup.select(".detail_badges .category"):
        category = normalize_text(node.get_text(" ", strip=True))
        if category and category not in source_categories:
            source_categories.append(category)

    return {
        "canonical_url": canonical_url,
        "title": (
            normalize_text(visible_title_node.get_text(" ", strip=True))
            if visible_title_node
            else meta_content(soup, property_name="og:title")
        ),
        "marketing_title": meta_content(soup, property_name="og:title"),
        "short_description": (
            meta_content(soup, name="description")
            or meta_content(soup, property_name="og:description")
        ),
        "main_image_url": resolve_relative_url(
            meta_content(soup, property_name="og:image"),
            canonical_url,
        ),
        "source_title": (
            normalize_text(soup.title.get_text(" ", strip=True))
            if soup.title
            else None
        ),
        "source_categories": source_categories,
        "page_state": extract_page_state(soup),
    }


def section_key(heading: Tag) -> str:
    if heading.get("id"):
        return str(heading.get("id"))
    text = normalize_for_matching(heading.get_text(" ", strip=True)) or "overview"
    return text.replace(" ", "-")


def classify_section(heading: str | None, heading_id: str | None = None) -> str:
    signal = normalize_for_matching(f"{heading_id or ''} {heading or ''}") or ""
    has_transport = any(
        phrase in signal
        for phrase in (
            "getting there",
            "how to get",
            "how to reach",
            "transport",
            "directions",
        )
    )
    has_contact = any(
        phrase in signal
        for phrase in (
            "contact",
            "further information",
            "visitor information",
            "practical information",
        )
    )
    if has_transport and has_contact:
        return "contact_transport"
    if has_transport:
        return "transportation"
    if has_contact:
        return "contact"
    if any(
        phrase in signal
        for phrase in (
            "activities",
            "things to do",
            "what to do",
            "reasons to visit",
            "experience highlights",
        )
    ):
        return "activities"
    if any(
        phrase in signal
        for phrase in (
            "places to visit",
            "nearby attractions",
            "what to see",
            "places to explore",
        )
    ):
        return "places"
    return "overview"


def is_table_of_contents_item(node: Tag) -> bool:
    parent = node.find_parent(["ol", "ul"])
    if parent is None:
        return False
    identity = f"{parent.get('id', '')} {' '.join(parent.get('class', []))}".casefold()
    return "toc" in identity or "table-of-contents" in identity


def extract_article(
    soup: BeautifulSoup,
    base_url: str,
) -> dict[str, Any]:
    article = soup.select_one("#article-content")
    if article is None:
        raise ScrapeError(
            "missing_critical_fields",
            "Critical selector #article-content was not found.",
        )

    paragraphs: list[dict[str, str]] = []
    current_section = "overview"
    current_section_type = "overview"
    current_heading = "Overview"

    for node in article.select("h1, h2, h3, h4, p, li"):
        if node.name in {"h1", "h2", "h3", "h4"}:
            current_heading = (
                normalize_text(node.get_text(" ", strip=True)) or "Overview"
            )
            current_section = section_key(node)
            current_section_type = classify_section(
                current_heading,
                str(node.get("id")) if node.get("id") else None,
            )
            continue

        if node.name == "li" and is_table_of_contents_item(node):
            continue
        text = normalize_text(node.get_text(" ", strip=True))
        if not text or text.casefold() == "table of contents":
            continue

        paragraphs.append(
            {
                "section": current_section,
                "section_type": current_section_type,
                "heading": current_heading,
                "text": text,
            }
        )

    activities = [
        paragraph["text"]
        for paragraph in paragraphs
        if paragraph["section_type"] == "activities"
    ]
    activity_extraction = "explicit_heading" if activities else "none"
    if not activities:
        activity_signals = (
            "activities",
            "offers visitors",
            "opportunity to",
            "chance to",
            "visitors can",
            "you can",
            "welcomed to",
            "welcome to try",
        )
        activities = [
            paragraph["text"]
            for paragraph in paragraphs
            if paragraph["section_type"] in {"overview", "places"}
            and any(
                signal in (normalize_for_matching(paragraph["text"]) or "")
                for signal in activity_signals
            )
        ]
        if activities:
            activity_extraction = "implicit_content_signals"
    narrative_paragraphs = [
        paragraph["text"]
        for paragraph in paragraphs
        if paragraph["section_type"]
        not in {"contact", "transportation", "contact_transport"}
    ]
    overview_paragraphs = [
        paragraph["text"]
        for paragraph in paragraphs
        if paragraph["section_type"] == "overview"
    ]

    additional_images: list[str] = []
    for image in article.select("img[src]"):
        resolved = resolve_relative_url(str(image.get("src")), base_url)
        if resolved and resolved not in additional_images:
            additional_images.append(resolved)

    return {
        "article": article,
        "paragraphs": paragraphs,
        "activities": activities,
        "activity_extraction": activity_extraction,
        "narrative_paragraphs": narrative_paragraphs,
        "overview_paragraphs": overview_paragraphs,
        "additional_image_urls": additional_images,
    }


def paragraph_label_and_value(paragraph: Tag) -> tuple[str | None, str | None]:
    full_text = normalize_text(paragraph.get_text(" ", strip=True))
    if not full_text:
        return None, None

    label_node = paragraph.find(["strong", "b"])
    if label_node:
        label = normalize_text(label_node.get_text(" ", strip=True))
        if label:
            value = re.sub(
                rf"^{re.escape(label)}\s*:\s*",
                "",
                full_text,
                flags=re.IGNORECASE,
            )
            return label, normalize_text(value)

    match = re.match(
        r"^(Address|Contact(?: Information)?|Phone|Telephone|Tel|Email|"
        r"Driving|By public transport|By car|By bus|By train|Opening Hours|"
        r"Operating Hours|Hours|Provider|Operator|Organiser|Organizer)\s*:\s*(.+)$",
        full_text,
        re.IGNORECASE,
    )
    if match:
        return normalize_text(match.group(1)), normalize_text(match.group(2))
    return None, full_text


def decode_cloudflare_email(encoded: str) -> str | None:
    """Decode Cloudflare's public email-protection value embedded in HTML."""
    try:
        data = bytes.fromhex(encoded)
    except ValueError:
        return None
    if len(data) < 2:
        return None
    key = data[0]
    return "".join(chr(value ^ key) for value in data[1:])


def section_type_for_node(node: Tag) -> tuple[str, str]:
    heading = node.find_previous(["h1", "h2", "h3", "h4"])
    if heading is None:
        return "overview", "Overview"
    heading_text = normalize_text(heading.get_text(" ", strip=True)) or "Overview"
    return (
        classify_section(
            heading_text,
            str(heading.get("id")) if heading.get("id") else None,
        ),
        heading_text,
    )


def add_transportation(
    items: list[dict[str, str]],
    mode: str,
    details: str | None,
) -> None:
    if not details:
        return
    normalized = normalize_for_matching(details)
    if normalized == normalize_for_matching(mode):
        return
    if normalized and all(
        normalize_for_matching(item["details"]) != normalized for item in items
    ):
        items.append({"mode": mode, "details": details})


def looks_like_transport(value: str | None) -> bool:
    signal = normalize_for_matching(value) or ""
    return any(
        phrase in signal
        for phrase in (
            "getting there",
            "how to get",
            "public transport",
            "by lrt",
            "by mrt",
            "by train",
            "by bus",
            "by car",
            "e hailing",
            "taxi",
            "parking",
            "station",
            "drive from",
            "minute drive",
            "short walk",
            "walking distance",
        )
    )


def truncate_embedded_labels(value: str | None) -> str | None:
    if value is None:
        return None
    return normalize_text(
        re.split(
            r"\s+(?:Email|Website|Operating Hours|Opening Hours|Tickets|"
            r"Contact(?: Information)?|Phone|Telephone|Tel)\s*:",
            value,
            maxsplit=1,
            flags=re.IGNORECASE,
        )[0]
    )


def derive_explicit_address(article: Tag) -> str | None:
    texts = [
        text
        for text in (
            normalize_text(node.get_text(" ", strip=True))
            for node in article.select("p, li")
        )
        if text
    ]

    # Prefer a numbered street address explicitly introduced by "located at".
    for text in reversed(texts):
        match = re.search(
            r"\blocated at\s+(\d[^.!?]{2,100})[.!?]",
            text,
            re.IGNORECASE,
        )
        if match:
            return normalize_text(match.group(1))

    # A locality followed directly by a Malaysian state is also safe to retain.
    state_names = list(MALAYSIAN_STATES) + list(STATE_ALIASES)
    for text in texts:
        for state_name in state_names:
            match = re.search(
                rf"\blocated in\s+([^,.]{{2,50}}),\s*({re.escape(state_name)})\b",
                text,
                re.IGNORECASE,
            )
            if match:
                state = match_state(match.group(2)) or normalize_text(match.group(2))
                return normalize_text(f"{match.group(1)}, {state}")
    return None


def extract_contact_information(article: Tag) -> dict[str, Any]:
    provider: str | None = None
    address: str | None = None
    operating_hours: str | None = None
    transportation: list[dict[str, str]] = []
    contact_paragraphs: list[str] = []

    transport_labels = {
        "driving",
        "by public transport",
        "by car",
        "by bus",
        "by train",
    }
    contact_labels = {"contact", "contact information", "phone", "telephone", "tel"}
    provider_labels = {"provider", "operator", "organiser", "organizer"}
    hours_labels = {"opening hours", "operating hours", "hours"}

    for paragraph in article.select("p, li"):
        if paragraph.name == "li" and is_table_of_contents_item(paragraph):
            continue
        label, value = paragraph_label_and_value(paragraph)
        full_text = normalize_text(paragraph.get_text(" ", strip=True))
        if not full_text:
            continue
        label_key = (normalize_for_matching(label) or "")
        section_type, heading = section_type_for_node(paragraph)
        strong = paragraph.find(["strong", "b"])
        strong_text = (
            normalize_text(strong.get_text(" ", strip=True)) if strong else None
        )
        standalone_strong = bool(strong_text and strong_text == full_text)

        if provider is None and section_type in {"contact", "contact_transport"}:
            if (
                standalone_strong
                and normalize_for_matching(strong_text)
                not in contact_labels | provider_labels | hours_labels
                and not looks_like_transport(strong_text)
            ):
                provider = strong_text

        if label_key == "address" and value:
            address = truncate_embedded_labels(value)
        elif label_key in provider_labels and value:
            provider = value
        elif label_key in hours_labels and value:
            operating_hours = value
        elif label_key in transport_labels and value:
            add_transportation(transportation, label or heading, value)
        elif section_type == "transportation" and looks_like_transport(
            f"{label or ''} {value or full_text}"
        ):
            if label_key not in {
                "address",
                "email",
                *contact_labels,
                *provider_labels,
                *hours_labels,
            }:
                add_transportation(
                    transportation,
                    label or heading,
                    value or full_text,
                )
        elif section_type == "contact_transport" and looks_like_transport(
            f"{label or ''} {value or full_text}"
        ):
            add_transportation(
                transportation,
                label or heading,
                value or full_text,
            )

        if label_key in contact_labels:
            contact_paragraphs.append(value or full_text)
        elif section_type in {"contact", "contact_transport"} and (
            re.search(r"(?:\+?6?0\s*)?1\d[\d\s()\-]{6,}", full_text)
            or paragraph.select_one('a[href^="tel:"]')
        ):
            contact_paragraphs.append(full_text)

        if operating_hours is None:
            hours_match = re.search(
                r"\b(?:Operating|Opening) hours?\s*:\s*(.+?)"
                r"(?=\s+(?:Tickets|Contact|Email|Website|Address)\s*:|$)",
                full_text,
                re.IGNORECASE,
            )
            if hours_match:
                operating_hours = normalize_text(hours_match.group(1))
            else:
                open_daily_match = re.search(
                    r"\bopen daily from\s+(.+?)(?:[.!?]|$)",
                    full_text,
                    re.IGNORECASE,
                )
                if open_daily_match:
                    operating_hours = normalize_text(
                        f"Daily from {open_daily_match.group(1)}"
                    )

    if address is None:
        address = derive_explicit_address(article)

    phone_numbers: list[dict[str, str | None]] = []
    for contact_text in contact_paragraphs:
        for segment in re.split(r"\||\bor\b", contact_text, flags=re.IGNORECASE):
            match = re.search(r"\+?\d[\d\s()\-]{6,}\d", segment)
            if not match:
                continue
            display_number = normalize_text(match.group(0))
            contact_name = normalize_text(segment[: match.start()].strip(" :-"))
            normalized_number = (
                normalize_phone_number(display_number) if display_number else None
            )
            if not display_number or any(
                item["normalized_number"] == normalized_number
                for item in phone_numbers
            ):
                continue
            phone_numbers.append(
                {
                    "name": contact_name,
                    "number": display_number,
                    "normalized_number": normalized_number,
                }
            )

    email: str | None = None
    email_node = article.select_one("[data-cfemail]")
    if email_node:
        email = decode_cloudflare_email(str(email_node.get("data-cfemail")))
    if email is None:
        mail_link = article.select_one('a[href^="mailto:"]')
        if mail_link:
            email = normalize_text(str(mail_link.get("href"))[7:].split("?", 1)[0])

    return {
        "provider": provider,
        "address": address,
        "operating_hours": operating_hours,
        "phone_numbers": phone_numbers,
        "email": email,
        "transportation": transportation,
    }


def extract_state(
    address: str | None,
    page_state: str | None,
    article_text: str | None,
) -> str | None:
    """Use address/page metadata before broader article mentions."""
    for source_text in (address, page_state, article_text):
        state = match_state(source_text)
        if state:
            return state
    return None


def signal_present(text: str | None, signal: str) -> bool:
    haystack = normalize_for_matching(text)
    needle = normalize_for_matching(signal)
    if not haystack or not needle:
        return False
    return re.search(rf"(?:^|\s){re.escape(needle)}(?:$|\s)", haystack) is not None


def classify_candidate_category(record: dict[str, Any]) -> dict[str, Any]:
    """Score existing project categories; never create or approve a category."""
    experience = record["experience"]
    source = record["source"]
    content = record["content"]
    weighted_fields = (
        (experience.get("title"), 3),
        (" ".join(source.get("source_categories", [])), 5),
        (experience.get("short_description"), 2),
        (experience.get("full_description"), 1),
        (" ".join(content.get("activities", [])), 2),
    )

    scores: dict[int, int] = {}
    matched_signals: dict[int, list[str]] = {}
    for category_id, rule in CATEGORY_RULES.items():
        score = 0
        matches: list[str] = []
        for signal in rule["signals"]:
            signal_score = sum(
                weight
                for value, weight in weighted_fields
                if signal_present(value, signal)
            )
            if signal_score:
                score += signal_score
                matches.append(signal)
        scores[category_id] = score
        matched_signals[category_id] = matches

    ranked = sorted(scores.items(), key=lambda item: (-item[1], item[0]))
    winner_id, winner_score = ranked[0]
    second_score = ranked[1][1]
    margin = winner_score - second_score

    if winner_score == 0:
        return {
            "category_id": None,
            "category_name": None,
            "confidence": "low",
            "review_required": True,
            "scores": {
                CATEGORY_RULES[key]["name"]: value for key, value in scores.items()
            },
            "matched_signals": [],
        }

    if winner_score >= 8 and margin >= 3:
        confidence = "high"
    elif winner_score >= 4 and margin >= 1:
        confidence = "medium"
    else:
        confidence = "low"

    return {
        "category_id": winner_id,
        "category_name": CATEGORY_RULES[winner_id]["name"],
        "confidence": confidence,
        "review_required": confidence != "high",
        "scores": {
            CATEGORY_RULES[key]["name"]: value for key, value in scores.items()
        },
        "matched_signals": matched_signals[winner_id],
    }


def calculate_source_hash(stable_content: dict[str, Any]) -> str:
    encoded = json.dumps(
        stable_content,
        ensure_ascii=False,
        sort_keys=True,
        separators=(",", ":"),
    ).encode("utf-8")
    return hashlib.sha256(encoded).hexdigest()


def extraction_warnings(record: dict[str, Any]) -> list[str]:
    warnings: list[str] = []
    checks = {
        "short description": record["experience"]["short_description"],
        "state": record["experience"]["state"],
        "address": record["experience"]["address"],
        "activities section": record["content"]["activities"],
        "transportation section": record["content"]["transportation"],
        "contact numbers": record["contact"]["phone_numbers"],
        "main image": record["images"]["main"],
    }
    for label, value in checks.items():
        if not value:
            warnings.append(f"No {label} extracted")
    if record["content"].get("activity_extraction") == "implicit_content_signals":
        warnings.append(
            "Activities came from general content signals and require review"
        )
    return warnings


def build_raw_record(
    source_url: str,
    soup: BeautifulSoup,
    scraped_at: str,
    fetch_result: FetchResult,
) -> dict[str, Any]:
    metadata = extract_metadata(soup, source_url)
    article = extract_article(soup, metadata["canonical_url"])
    contact = extract_contact_information(article["article"])

    full_description = normalize_text("\n\n".join(article["narrative_paragraphs"]))
    state = extract_state(
        contact["address"],
        metadata["page_state"],
        full_description,
    )
    normalized_source_url = normalize_canonical_url(metadata["canonical_url"])

    stable_content = {
        "canonical_url": normalized_source_url,
        "title": metadata["title"],
        "short_description": metadata["short_description"],
        "full_description": full_description,
        "state": state,
        "address": contact["address"],
        "provider": contact["provider"],
        "operating_hours": contact["operating_hours"],
        "activities": article["activities"],
        "transportation": contact["transportation"],
        "phone_numbers": contact["phone_numbers"],
        "email": contact["email"],
        "main_image_url": metadata["main_image_url"],
        "additional_image_urls": article["additional_image_urls"],
        "source_categories": metadata["source_categories"],
    }

    record: dict[str, Any] = {
        "source": {
            "url": source_url,
            "canonical_url": metadata["canonical_url"],
            "source_title": metadata["source_title"],
            "marketing_title": metadata["marketing_title"],
            "source_categories": metadata["source_categories"],
            "scraped_at": scraped_at,
            "http_status": fetch_result.status_code,
            "content_type": fetch_result.content_type,
            "rate_limit": fetch_result.rate_limit,
            "rate_remaining": fetch_result.rate_remaining,
        },
        "experience": {
            "title": metadata["title"],
            "short_description": metadata["short_description"],
            "full_description": full_description,
            "state": state,
            "address": contact["address"],
            "provider": contact["provider"],
            "price": None,
            "duration": None,
            "start_date": None,
            "end_date": None,
            "operating_hours": contact["operating_hours"],
            "latitude": None,
            "longitude": None,
            "status": None,
        },
        "content": {
            "paragraphs": article["paragraphs"],
            "activities": article["activities"],
            "activity_extraction": article["activity_extraction"],
            "transportation": contact["transportation"],
        },
        "contact": {
            "phone_numbers": contact["phone_numbers"],
            "email": contact["email"],
        },
        "images": {
            "main": metadata["main_image_url"],
            "additional": article["additional_image_urls"],
            "downloaded": False,
        },
        "candidate_mapping": {
            "type_id": CULTURAL_EXPERIENCE_TYPE["id"],
            "type_name": CULTURAL_EXPERIENCE_TYPE["name"],
            "classification_source": "project candidate mapping plus keyword signals",
            "status": None,
        },
        "normalization": {
            "normalized_source_url": normalized_source_url,
            "normalized_title": normalize_title(metadata["title"]),
            "normalized_location": normalize_location(contact["address"]),
            "source_content_hash": calculate_source_hash(stable_content),
        },
    }
    record["candidate_mapping"].update(classify_candidate_category(record))
    record["extraction_warnings"] = extraction_warnings(record)
    return record


def first_sentence(value: str) -> str:
    match = re.search(r"^.*?[.!?](?:\s|$)", value)
    return normalize_text(match.group(0) if match else value) or value


def build_review_record(raw: dict[str, Any]) -> dict[str, Any]:
    experience = raw["experience"]
    content = raw["content"]
    contact = raw["contact"]
    images = raw["images"]
    mapping = raw["candidate_mapping"]
    normalization = raw["normalization"]

    overview_paragraphs = [
        paragraph["text"]
        for paragraph in content["paragraphs"]
        if paragraph["section_type"] == "overview"
    ]
    candidate_description = normalize_text("\n\n".join(overview_paragraphs[:2]))
    if candidate_description is None:
        candidate_description = experience["short_description"]
        description_provenance = "source_metadata_fallback"
    else:
        description_provenance = "source_paragraph_excerpt"

    phone_numbers = contact["phone_numbers"]
    candidate_contact = (
        phone_numbers[0]["number"] if len(phone_numbers) == 1 else None
    )
    contact_review_required = len(phone_numbers) != 1

    activities_summary = " | ".join(
        first_sentence(item) for item in content["activities"]
    )
    transportation_summary = " | ".join(
        f'{item["mode"]}: {item["details"]}'
        for item in content["transportation"]
    )

    review_notes = [
        "Candidate description requires editorial review.",
        "Type/category are candidate project mappings, not source database IDs.",
    ]
    if mapping["review_required"]:
        review_notes.append("Category selection requires manual review.")
    if contact_review_required:
        review_notes.append("Contact selection requires manual review.")
    if images["main"]:
        review_notes.append("Image rights require manual approval.")

    return {
        "source_url": raw["source"]["canonical_url"],
        "normalized_source_url": normalization["normalized_source_url"],
        "scrape_status": "success",
        "scrape_warning": "; ".join(raw["extraction_warnings"]),
        "experiences_name": experience["title"],
        "short_description": experience["short_description"],
        "description": candidate_description,
        "description_provenance": description_provenance,
        "location_name": experience["address"],
        "source_state": experience["state"],
        "price": experience["price"],
        "duration": experience["duration"],
        "start_date": experience["start_date"],
        "end_date": experience["end_date"],
        "operating_hours": experience["operating_hours"],
        "candidate_type_id": mapping["type_id"],
        "candidate_type_name": mapping["type_name"],
        "candidate_category_id": mapping["category_id"],
        "candidate_category_name": mapping["category_name"],
        "category_confidence": mapping["confidence"],
        "category_review_required": mapping["review_required"],
        "latitude": experience["latitude"],
        "longitude": experience["longitude"],
        "candidate_contact_number": candidate_contact,
        "contact_review_required": contact_review_required,
        "source_image_url": images["main"],
        "image_review_required": bool(images["main"]),
        "candidate_status": experience["status"],
        "provider": experience["provider"],
        "source_email": contact["email"],
        "activities_summary": activities_summary or None,
        "transportation_summary": transportation_summary or None,
        "normalized_title": normalization["normalized_title"],
        "normalized_location": normalization["normalized_location"],
        "source_content_hash": normalization["source_content_hash"],
        "generated_duplicate_possible": False,
        "generated_match_note": None,
        "database_duplicate_possible": None,
        "database_match_note": None,
        "review_status": "pending",
        "review_notes": " ".join(review_notes),
    }


def validate_raw_record(record: dict[str, Any]) -> None:
    required_values = {
        "canonical URL": record["source"]["canonical_url"],
        "title": record["experience"]["title"],
        "full description": record["experience"]["full_description"],
        "article paragraphs": record["content"]["paragraphs"],
    }
    missing = [name for name, value in required_values.items() if not value]
    if missing:
        raise ScrapeError(
            "missing_critical_fields",
            f"Critical extraction fields missing: {', '.join(missing)}",
        )


def read_url_file(path: Path) -> list[str]:
    if not path.is_file():
        raise ScrapeError("input_file_error", f"URL file not found: {path}")
    urls: list[str] = []
    for line in path.read_text(encoding="utf-8-sig").splitlines():
        value = line.strip()
        if value and not value.startswith("#"):
            urls.append(value)
    return urls


def deduplicate_input_urls(
    urls: Iterable[str],
) -> tuple[list[str], list[dict[str, str]]]:
    unique_urls: list[str] = []
    duplicate_inputs: list[dict[str, str]] = []
    seen: dict[str, str] = {}

    for source_url in urls:
        try:
            normalized = normalize_canonical_url(source_url)
        except ScrapeError:
            # Invalid URLs remain in the batch so they receive classified errors.
            unique_urls.append(source_url)
            continue

        if normalized in seen:
            duplicate_inputs.append(
                {
                    "source_url": source_url,
                    "normalized_source_url": normalized,
                    "first_source_url": seen[normalized],
                }
            )
            continue
        seen[normalized] = source_url
        unique_urls.append(source_url)

    return unique_urls, duplicate_inputs


FetchFunction = Callable[[requests.Session, str, float, int], FetchResult]


def process_batch(
    urls: list[str],
    session: requests.Session,
    *,
    timeout: float,
    delay_seconds: float,
    fetcher: FetchFunction = fetch_page,
) -> BatchResult:
    unique_urls, duplicate_inputs = deduplicate_input_urls(urls)
    records: list[dict[str, Any]] = []
    review_rows: list[dict[str, Any]] = []
    errors: list[dict[str, Any]] = []
    attempted_requests = 0

    for source_url in unique_urls:
        try:
            normalize_canonical_url(source_url)
        except ScrapeError as error:
            errors.append(
                {
                    "source_url": source_url,
                    "error_type": error.error_type,
                    "message": str(error),
                    "http_status": error.http_status,
                }
            )
            continue

        if attempted_requests > 0 and delay_seconds > 0:
            time.sleep(delay_seconds)
        attempted_requests += 1

        try:
            result = fetcher(session, source_url, timeout, 3)
            scraped_at = datetime.now(timezone.utc).isoformat().replace(
                "+00:00", "Z"
            )
            soup = BeautifulSoup(result.html, "html.parser")
            record = build_raw_record(source_url, soup, scraped_at, result)
            validate_raw_record(record)
            records.append(record)
            review_rows.append(build_review_record(record))
        except ScrapeError as error:
            errors.append(
                {
                    "source_url": source_url,
                    "error_type": error.error_type,
                    "message": str(error),
                    "http_status": error.http_status,
                }
            )
        except Exception as error:  # Keep one unexpected page failure isolated.
            errors.append(
                {
                    "source_url": source_url,
                    "error_type": "parsing_error",
                    "message": f"Unexpected parsing error: {error}",
                    "http_status": None,
                }
            )

    apply_generated_duplicate_flags(review_rows)
    return BatchResult(
        received_count=len(urls),
        records=records,
        review_rows=review_rows,
        errors=errors,
        duplicate_inputs=duplicate_inputs,
    )


def apply_generated_duplicate_flags(review_rows: list[dict[str, Any]]) -> None:
    groups: dict[tuple[str, str], list[int]] = {}
    for index, row in enumerate(review_rows):
        title = row["normalized_title"]
        location = row["normalized_location"]
        if title and location:
            groups.setdefault((title, location), []).append(index)

    for indexes in groups.values():
        if len(indexes) < 2:
            continue
        titles = [review_rows[index]["experiences_name"] for index in indexes]
        note = "Same normalized title and location in generated batch: " + "; ".join(
            titles
        )
        for index in indexes:
            review_rows[index]["generated_duplicate_possible"] = True
            review_rows[index]["generated_match_note"] = note


def project_root() -> Path:
    return Path(__file__).resolve().parents[2]


def load_database_experiences(
    root: Path,
) -> tuple[list[dict[str, Any]] | None, str | None]:
    """Read only IDs, titles, and locations through the existing Laravel DB config."""
    artisan = root / "artisan"
    if not artisan.is_file():
        return None, "Laravel artisan was not found; database check skipped."

    php_code = (
        "$rows = DB::table('experiences')->get(["
        "'experiences_id','experiences_name','location_name']);"
        "$json = json_encode($rows, JSON_UNESCAPED_UNICODE);"
        "echo 'LHM_DB_BEGIN'.base64_encode($json).'LHM_DB_END';"
    )
    try:
        result = subprocess.run(
            ["php", "artisan", "tinker", f"--execute={php_code}"],
            cwd=root,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=60,
            check=False,
        )
    except (OSError, subprocess.TimeoutExpired) as error:
        return None, f"Database check unavailable: {error}"

    output = f"{result.stdout}\n{result.stderr}"
    match = re.search(r"LHM_DB_BEGIN([A-Za-z0-9+/=]+)LHM_DB_END", output)
    if result.returncode != 0 or not match:
        return None, "Laravel database read failed; duplicate status is unknown."

    try:
        decoded = base64.b64decode(match.group(1)).decode("utf-8")
        rows = json.loads(decoded)
    except (ValueError, UnicodeDecodeError, json.JSONDecodeError) as error:
        return None, f"Laravel database result could not be decoded: {error}"

    if not isinstance(rows, list):
        return None, "Laravel database result was not a record list."
    return rows, None


def apply_database_duplicate_flags(
    review_rows: list[dict[str, Any]],
    database_rows: list[dict[str, Any]] | None,
    database_warning: str | None,
) -> None:
    if database_rows is None:
        for row in review_rows:
            row["database_duplicate_possible"] = None
            row["database_match_note"] = database_warning
        return

    normalized_database = [
        {
            "id": record.get("experiences_id"),
            "title": record.get("experiences_name"),
            "location": record.get("location_name"),
            "normalized_title": normalize_title(record.get("experiences_name")),
            "normalized_location": normalize_location(record.get("location_name")),
        }
        for record in database_rows
    ]

    for row in review_rows:
        exact_matches = [
            record
            for record in normalized_database
            if row["normalized_title"]
            and row["normalized_location"]
            and record["normalized_title"] == row["normalized_title"]
            and record["normalized_location"] == row["normalized_location"]
        ]
        title_matches = [
            record
            for record in normalized_database
            if row["normalized_title"]
            and record["normalized_title"] == row["normalized_title"]
        ]

        matches = exact_matches or title_matches
        if matches:
            row["database_duplicate_possible"] = True
            match_kind = (
                "normalized title and location"
                if exact_matches
                else "normalized title only; location differs or is unavailable"
            )
            row["database_match_note"] = (
                f"Possible {match_kind} match: "
                + "; ".join(
                    f'ID {record["id"]} {record["title"]} ({record["location"] or "no location"})'
                    for record in matches
                )
            )
        else:
            row["database_duplicate_possible"] = False
            row["database_match_note"] = "No normalized title/location match found."


def validate_review_records(records: Iterable[dict[str, Any]]) -> None:
    seen_urls: set[str] = set()
    for row_number, record in enumerate(records, start=1):
        missing_columns = [
            column for column in REVIEW_COLUMNS if column not in record
        ]
        if missing_columns:
            raise ScrapeError(
                "review_validation_error",
                f"Review row {row_number} is missing: {', '.join(missing_columns)}",
            )
        source_url = record["normalized_source_url"]
        if source_url in seen_urls:
            raise ScrapeError(
                "review_validation_error",
                f"Duplicate normalized source URL in review rows: {source_url}",
            )
        seen_urls.add(source_url)
        if record["review_status"] != "pending":
            raise ScrapeError(
                "review_validation_error",
                "New review records must have review_status=pending.",
            )


def write_json(path: Path, batch: BatchResult, generated_at: str) -> None:
    payload = {
        "generated_at": generated_at,
        "urls_received": batch.received_count,
        "records": batch.records,
        "errors": batch.errors,
        "duplicate_inputs": batch.duplicate_inputs,
    }
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )


def csv_value(value: Any) -> Any:
    if value is None:
        return ""
    if isinstance(value, bool):
        return "true" if value else "false"
    return value


def write_csv(path: Path, records: list[dict[str, Any]]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8-sig", newline="") as handle:
        writer = csv.DictWriter(
            handle,
            fieldnames=REVIEW_COLUMNS,
            extrasaction="raise",
        )
        writer.writeheader()
        for record in records:
            writer.writerow(
                {key: csv_value(record[key]) for key in REVIEW_COLUMNS}
            )


def default_output_directory() -> Path:
    return project_root() / "storage" / "app" / "imports" / "tourism-malaysia"


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Batch-extract Tourism Malaysia pages for manual review."
    )
    parser.add_argument(
        "urls",
        nargs="*",
        help="Tourism Malaysia detail URLs supplied directly.",
    )
    parser.add_argument(
        "--file",
        type=Path,
        help="Text file containing one approved URL per line.",
    )
    parser.add_argument(
        "--output-dir",
        type=Path,
        default=default_output_directory(),
        help="Directory for raw.json and review.csv.",
    )
    parser.add_argument("--timeout", type=float, default=25.0)
    parser.add_argument(
        "--delay",
        type=float,
        default=DEFAULT_DELAY_SECONDS,
        help="Seconds between pages; defaults to a conservative 3 seconds.",
    )
    parser.add_argument(
        "--skip-database-check",
        action="store_true",
        help="Skip the read-only duplicate check against Laravel's database.",
    )
    return parser.parse_args()


def main() -> int:
    arguments = parse_arguments()
    if arguments.timeout <= 0:
        print("Scrape failed: --timeout must be greater than zero.", file=sys.stderr)
        return 2
    if arguments.delay < 0:
        print("Scrape failed: --delay cannot be negative.", file=sys.stderr)
        return 2

    urls = list(arguments.urls)
    try:
        if arguments.file:
            urls.extend(read_url_file(arguments.file))
    except ScrapeError as error:
        print(f"Scrape failed: {error}", file=sys.stderr)
        return 2
    if not urls:
        # Preserve the original Stage 1 no-argument test behavior.
        urls = [TEST_URL]

    generated_at = datetime.now(timezone.utc).isoformat().replace("+00:00", "Z")
    session = requests.Session()
    session.headers.update(
        {
            "User-Agent": USER_AGENT,
            "Accept": "text/html,application/xhtml+xml",
        }
    )

    try:
        batch = process_batch(
            urls,
            session,
            timeout=arguments.timeout,
            delay_seconds=arguments.delay,
        )
    finally:
        session.close()

    if arguments.skip_database_check:
        database_rows = None
        database_warning = "Database check skipped by command option."
    else:
        database_rows, database_warning = load_database_experiences(project_root())
    apply_database_duplicate_flags(
        batch.review_rows,
        database_rows,
        database_warning,
    )

    try:
        validate_review_records(batch.review_rows)
        write_json(arguments.output_dir / "raw.json", batch, generated_at)
        write_csv(arguments.output_dir / "review.csv", batch.review_rows)
    except ScrapeError as error:
        print(f"Output validation failed: {error}", file=sys.stderr)
        return 1

    possible_database_duplicates = sum(
        row["database_duplicate_possible"] is True for row in batch.review_rows
    )
    category_review_required = sum(
        row["category_review_required"] is True for row in batch.review_rows
    )
    image_review_required = sum(
        row["image_review_required"] is True for row in batch.review_rows
    )

    summary = {
        "urls_received": batch.received_count,
        "successfully_scraped": len(batch.records),
        "failed": len(batch.errors),
        "duplicate_input_urls": len(batch.duplicate_inputs),
        "review_rows_generated": len(batch.review_rows),
        "possible_database_duplicates": possible_database_duplicates,
        "category_review_required": category_review_required,
        "image_review_required": image_review_required,
        "raw_json": str(arguments.output_dir / "raw.json"),
        "review_csv": str(arguments.output_dir / "review.csv"),
        "database_check_warning": database_warning,
    }
    print("Batch completed")
    print(json.dumps(summary, ensure_ascii=False, indent=2))
    return 0 if batch.records else 1


if __name__ == "__main__":
    raise SystemExit(main())
