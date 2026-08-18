import unittest

import requests

from scrape import (
    FetchResult,
    ScrapeError,
    calculate_source_hash,
    classify_candidate_category,
    deduplicate_input_urls,
    normalize_canonical_url,
    normalize_location,
    normalize_title,
    process_batch,
)


class ScraperUnitTest(unittest.TestCase):
    def test_url_normalization_and_duplicate_detection(self):
        first = "https://www.malaysia.travel/explore/example/?tracking=1"
        second = "https://www.malaysia.travel/explore/example"

        self.assertEqual(
            normalize_canonical_url(first),
            "https://www.malaysia.travel/explore/example",
        )

        unique, duplicates = deduplicate_input_urls([first, second])
        self.assertEqual(unique, [first])
        self.assertEqual(len(duplicates), 1)

    def test_non_tourism_malaysia_url_is_rejected(self):
        with self.assertRaises(ScrapeError) as context:
            normalize_canonical_url("https://example.com/experience")
        self.assertEqual(context.exception.error_type, "invalid_url")

    def test_title_and_location_normalization(self):
        self.assertEqual(
            normalize_title("  Pinang Peranakan Mansion! "),
            "pinang peranakan mansion",
        )
        self.assertEqual(
            normalize_location("29, Church Street,  Penang"),
            "29 church street penang",
        )

    def test_source_hash_is_deterministic(self):
        content = {"title": "Example", "location": "Selangor"}
        self.assertEqual(
            calculate_source_hash(content),
            calculate_source_hash(content),
        )
        self.assertEqual(len(calculate_source_hash(content)), 64)

    def test_category_candidate_mapping(self):
        record = {
            "source": {"source_categories": ["Culture & Heritage"]},
            "experience": {
                "title": "Historic Cultural Village",
                "short_description": "A traditional heritage museum.",
                "full_description": "Visitors learn about local history.",
            },
            "content": {"activities": []},
        }
        result = classify_candidate_category(record)
        self.assertEqual(result["category_id"], 3)
        self.assertEqual(result["category_name"], "Heritage")
        self.assertIn(result["confidence"], {"medium", "high"})

    def test_batch_continues_after_one_page_fails(self):
        good_url = "https://www.malaysia.travel/explore/good-page"
        bad_url = "https://www.malaysia.travel/explore/missing-page"
        html = """
            <html><head>
              <title>Good Page</title>
              <meta name="description" content="A cultural heritage place.">
              <link rel="canonical" href="https://www.malaysia.travel/explore/good-page">
            </head><body><div class="experience_detail_page">
              <section class="py-4 bg-F7F7F7"><h2>Good Page</h2></section>
              <div id="article-content"><h2>About</h2><p>Heritage content.</p></div>
            </div></body></html>
        """

        def fake_fetch(session, url, timeout, max_attempts):
            if url == bad_url:
                raise ScrapeError("http_404", "Not found", 404)
            return FetchResult(html, 200, "text/html", None, None)

        result = process_batch(
            [bad_url, good_url],
            requests.Session(),
            timeout=1,
            delay_seconds=0,
            fetcher=fake_fetch,
        )
        self.assertEqual(len(result.records), 1)
        self.assertEqual(len(result.review_rows), 1)
        self.assertEqual(len(result.errors), 1)
        self.assertEqual(result.errors[0]["error_type"], "http_404")


if __name__ == "__main__":
    unittest.main()
