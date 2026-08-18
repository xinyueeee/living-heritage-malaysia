import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const createPlaceholder = (name) => {
    const placeholder = document.createElement('div');
    placeholder.className = 'map-popup-image map-popup-image-placeholder';
    placeholder.setAttribute('role', 'img');
    placeholder.setAttribute('aria-label', `Image unavailable for ${name}`);
    placeholder.innerHTML = '<svg viewBox="0 0 64 48" fill="none" aria-hidden="true"><circle cx="46" cy="13" r="5" fill="currentColor" opacity=".75"/><path d="M8 39 24 22l9 9 7-7 16 15H8Z" fill="currentColor" opacity=".72"/><path d="M8 39h48" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';

    return placeholder;
};

const appendText = (parent, className, text) => {
    if (!text) {
        return;
    }

    const element = document.createElement('p');
    element.className = className;
    element.textContent = text;
    parent.appendChild(element);
};

const createPopup = (marker) => {
    const popup = document.createElement('article');
    popup.className = 'experience-map-popup';

    const imageFrame = document.createElement('div');
    imageFrame.className = 'map-popup-image-frame';
    imageFrame.appendChild(createPlaceholder(marker.name));

    if (marker.imageUrl) {
        const image = document.createElement('img');
        image.className = 'map-popup-image map-popup-image-actual';
        image.src = marker.imageUrl;
        image.alt = marker.name;
        if (marker.externalImage) {
            image.referrerPolicy = 'no-referrer';
        }
        image.addEventListener('error', () => image.remove());
        imageFrame.appendChild(image);
    }

    popup.appendChild(imageFrame);

    const content = document.createElement('div');
    content.className = 'map-popup-content';
    const heading = document.createElement('h3');
    heading.textContent = marker.name;
    content.appendChild(heading);

    const date = marker.startDate
        ? marker.endDate && marker.endDate !== marker.startDate
            ? `${marker.startDate} – ${marker.endDate}`
            : marker.startDate
        : null;
    appendText(content, 'map-popup-date', date);
    appendText(content, 'map-popup-location', marker.location);
    if (marker.coordinatePrecision === 'approximate') {
        appendText(content, 'map-popup-precision', 'Approximate location');
    } else if (Number.isFinite(marker.distanceKm)) {
        appendText(content, 'map-popup-distance', `Approx. ${formatDistance(marker.distanceKm)} away`);
    }
    appendText(content, 'map-popup-description', marker.shortDescription);

    const details = document.createElement('a');
    details.className = 'map-popup-details';
    details.href = marker.detailsUrl;
    details.textContent = 'View Details →';
    content.appendChild(details);
    popup.appendChild(content);

    return popup;
};

const toRadians = (degrees) => degrees * (Math.PI / 180);

const haversineDistanceKm = (originLatitude, originLongitude, targetLatitude, targetLongitude) => {
    const earthRadiusKm = 6371;
    const latitudeDelta = toRadians(targetLatitude - originLatitude);
    const longitudeDelta = toRadians(targetLongitude - originLongitude);
    const originLatitudeRadians = toRadians(originLatitude);
    const targetLatitudeRadians = toRadians(targetLatitude);
    const haversine = Math.sin(latitudeDelta / 2) ** 2
        + Math.cos(originLatitudeRadians)
        * Math.cos(targetLatitudeRadians)
        * Math.sin(longitudeDelta / 2) ** 2;
    const boundedHaversine = Math.min(1, Math.max(0, haversine));

    return earthRadiusKm * 2 * Math.atan2(Math.sqrt(boundedHaversine), Math.sqrt(1 - boundedHaversine));
};

const formatDistance = (distanceKm) => distanceKm < 1
    ? `${Math.round(distanceKm * 1000)} m`
    : `${distanceKm.toFixed(1)} km`;

const markerGroups = {
    heritage: { label: 'Heritage', symbol: '▥', color: '#8a5528' },
    festival: { label: 'Festivals', symbol: '✦', color: '#a52a1a' },
    culinary: { label: 'Culinary', symbol: '♨', color: '#b86613' },
    performance: { label: 'Performance', symbol: '♪', color: '#7d3c98' },
    sports: { label: 'Sports & Outdoor', symbol: '⚑', color: '#28745b' },
    craft: { label: 'Craft & Workshop', symbol: '✥', color: '#9a6a18' },
    generic: { label: 'Other Experiences', symbol: '◆', color: '#5a4a42' },
};

const getMarkerGroup = (marker) => {
    const taxonomy = `${marker.categoryName ?? ''} ${marker.typeName ?? ''}`.trim().toLowerCase();

    if (taxonomy.includes('food') || taxonomy.includes('culinary')) return 'culinary';
    if (taxonomy.includes('music') || taxonomy.includes('performance') || taxonomy.includes('entertainment')) return 'performance';
    if (['sport', 'nature', 'outdoor', 'trail', 'marathon'].some((term) => taxonomy.includes(term))) return 'sports';
    if (['craft', 'workshop', 'batik'].some((term) => taxonomy.includes(term))) return 'craft';
    if (taxonomy.includes('festival') || taxonomy.includes('national celebration')) return 'festival';
    if (['heritage', 'cultural', 'arts', 'museum'].some((term) => taxonomy.includes(term))) return 'heritage';

    return 'generic';
};

const createExperienceIcon = (marker) => {
    const group = markerGroups[marker.markerGroup] ?? markerGroups.generic;

    return L.divIcon({
        className: 'experience-div-marker',
        html: `<span style="--marker-color: ${group.color}" aria-hidden="true"><b>${group.symbol}</b></span>`,
        iconSize: [34, 42],
        iconAnchor: [17, 40],
        popupAnchor: [0, -38],
    });
};

const createPreviewPopup = (marker) => {
    const popup = document.createElement('div');
    popup.className = 'experience-map-preview-popup';
    const heading = document.createElement('strong');
    heading.textContent = marker.name;
    popup.appendChild(heading);
    appendText(popup, 'map-popup-location', marker.location);
    const details = document.createElement('a');
    details.className = 'map-popup-details';
    details.href = marker.detailsUrl;
    details.textContent = 'View Details →';
    popup.appendChild(details);

    return popup;
};

const initializePreviewMap = (container, markers) => {
    const map = L.map(container, { scrollWheelZoom: false }).setView([4.2, 109.5], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    const bounds = [];
    markers.forEach((marker) => {
        const coordinates = [marker.latitude, marker.longitude];
        const group = markerGroups[marker.markerGroup] ?? markerGroups.generic;
        L.marker(coordinates, {
            alt: `${group.label}: ${marker.name}`,
            title: marker.name,
            icon: createExperienceIcon(marker),
        }).addTo(map).bindPopup(createPreviewPopup(marker), { maxWidth: 240 });
        bounds.push(coordinates);
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [20, 20], maxZoom: 9 });
    }
};

const createNearbyCard = (marker) => {
    const card = document.createElement('article');
    card.className = 'nearby-experience-card';

    const imageFrame = document.createElement('div');
    imageFrame.className = 'nearby-experience-image-frame';
    imageFrame.appendChild(createPlaceholder(marker.name));
    if (marker.imageUrl) {
        const image = document.createElement('img');
        image.className = 'nearby-experience-image map-popup-image-actual';
        image.src = marker.imageUrl;
        image.alt = marker.name;
        if (marker.externalImage) {
            image.referrerPolicy = 'no-referrer';
        }
        image.addEventListener('error', () => image.remove());
        imageFrame.appendChild(image);
    }
    card.appendChild(imageFrame);

    const content = document.createElement('div');
    content.className = 'nearby-experience-content';
    const heading = document.createElement('h4');
    heading.textContent = marker.name;
    content.appendChild(heading);
    const badge = document.createElement('span');
    badge.className = 'nearby-experience-badge';
    badge.textContent = marker.categoryName || marker.typeName || markerGroups[marker.markerGroup].label;
    content.appendChild(badge);
    appendText(content, 'nearby-experience-location', marker.location);
    appendText(content, 'nearby-experience-distance', `Approx. ${formatDistance(marker.distanceKm)} away`);

    const details = document.createElement('a');
    details.href = marker.detailsUrl;
    details.textContent = 'View Details →';
    details.className = 'map-popup-details';
    content.appendChild(details);
    card.appendChild(content);

    return card;
};

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('experience-map');
    const previewContainer = document.getElementById('experience-map-preview');

    const markers = Array.isArray(window.livingHeritageExperienceMarkers)
        ? window.livingHeritageExperienceMarkers
            .filter((marker) =>
                Number.isFinite(marker.latitude)
                && Number.isFinite(marker.longitude)
                && marker.latitude >= -90
                && marker.latitude <= 90
                && marker.longitude >= -180
                && marker.longitude <= 180
                && !(marker.latitude === 0 && marker.longitude === 0)
            )
            .map((marker) => ({ ...marker, markerGroup: getMarkerGroup(marker) }))
        : [];

    if (previewContainer) {
        initializePreviewMap(previewContainer, markers);
    }
    if (!container) {
        return;
    }
    const locationButton = document.getElementById('use-my-location');
    const viewAllButton = document.getElementById('view-all-experiences');
    const locationStatus = document.getElementById('experience-location-status');
    const nearbySection = document.getElementById('nearby-experiences');
    const nearbyHeading = document.getElementById('nearby-experiences-heading');
    const nearbySummary = document.getElementById('nearby-experiences-summary');
    const nearbyList = document.getElementById('nearby-experiences-list');
    const categoryFilters = document.getElementById('map-category-filters');
    const nearbyRadiusKm = 50;
    const nearbyLimit = 5;

    const map = L.map(container, { scrollWheelZoom: false }).setView([4.2, 109.5], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    const bounds = [];
    const experienceLayers = new Map();
    const availableGroups = [...new Set(markers.map((marker) => marker.markerGroup))];
    const activeGroups = new Set(availableGroups);
    let userMarker = null;
    let userPosition = null;
    markers.forEach((marker) => {
        const coordinates = [marker.latitude, marker.longitude];
        const group = markerGroups[marker.markerGroup] ?? markerGroups.generic;
        const layer = L.marker(coordinates, {
            alt: `${group.label}: ${marker.name}`,
            title: marker.name,
            icon: createExperienceIcon(marker),
        })
            .addTo(map)
            .bindPopup(createPopup(marker), { maxWidth: 300 });
        experienceLayers.set(marker.detailsUrl, { layer, marker });
        bounds.push(coordinates);
    });

    availableGroups.forEach((groupKey) => {
        const group = markerGroups[groupKey] ?? markerGroups.generic;
        const label = document.createElement('label');
        label.className = 'map-category-filter';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.checked = true;
        checkbox.value = groupKey;
        const symbol = document.createElement('span');
        symbol.className = 'map-legend-marker';
        symbol.style.setProperty('--marker-color', group.color);
        const glyph = document.createElement('b');
        glyph.textContent = group.symbol;
        symbol.appendChild(glyph);
        const text = document.createElement('span');
        text.textContent = group.label;
        label.append(checkbox, symbol, text);
        categoryFilters?.appendChild(label);

        checkbox.addEventListener('change', () => {
            if (checkbox.checked) {
                activeGroups.add(groupKey);
            } else {
                activeGroups.delete(groupKey);
            }

            experienceLayers.forEach(({ layer, marker }) => {
                if (marker.markerGroup !== groupKey) {
                    return;
                }
                if (checkbox.checked) {
                    layer.addTo(map);
                } else {
                    layer.removeFrom(map);
                }
            });
            renderNearbyExperiences();
        });
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [28, 28], maxZoom: 11 });
    }

    const showAllExperiences = () => {
        const visibleBounds = markers
            .filter((marker) => activeGroups.has(marker.markerGroup))
            .map((marker) => [marker.latitude, marker.longitude]);
        if (visibleBounds.length > 0) {
            map.fitBounds(visibleBounds, { padding: [28, 28], maxZoom: 11 });
        } else {
            map.setView([4.2, 109.5], 5);
        }
    };

    viewAllButton?.addEventListener('click', showAllExperiences);

    const showLocationError = (message) => {
        locationStatus.textContent = message;
        locationButton.disabled = false;
        locationButton.removeAttribute('aria-busy');
    };

    const renderNearbyExperiences = () => {
        if (!userPosition) {
            return [];
        }

        const exactMarkers = markers
            .filter((marker) => marker.coordinatePrecision === 'exact' && activeGroups.has(marker.markerGroup))
            .map((marker) => ({
                ...marker,
                distanceKm: haversineDistanceKm(
                    userPosition.latitude,
                    userPosition.longitude,
                    marker.latitude,
                    marker.longitude,
                ),
            }))
            .sort((first, second) => first.distanceKm - second.distanceKm);
        const withinRadius = exactMarkers.filter((marker) => marker.distanceKm <= nearbyRadiusKm);
        const nearbyMarkers = (withinRadius.length > 0 ? withinRadius : exactMarkers).slice(0, nearbyLimit);

        nearbyList.replaceChildren(...nearbyMarkers.map(createNearbyCard));
        nearbySection.hidden = false;
        if (nearbyMarkers.length === 0) {
            nearbyHeading.textContent = 'Nearby Cultural Experiences';
            nearbySummary.textContent = 'No exact-coordinate Experiences are available for the selected map categories.';
        } else if (withinRadius.length > 0) {
            nearbyHeading.textContent = 'Nearby Cultural Experiences';
            nearbySummary.textContent = `Up to ${nearbyLimit} within ${nearbyRadiusKm} km · Sorted by nearest`;
        } else {
            nearbyHeading.textContent = 'Nearest Cultural Experiences';
            nearbySummary.textContent = `None within ${nearbyRadiusKm} km · Showing nearest available`;
        }

        return nearbyMarkers;
    };

    locationButton?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            showLocationError('Location services are not supported by this browser. You can still explore experiences on the map.');
            return;
        }

        locationButton.disabled = true;
        locationButton.setAttribute('aria-busy', 'true');
        locationStatus.textContent = 'Finding your location…';

        navigator.geolocation.getCurrentPosition((position) => {
            const userCoordinates = [position.coords.latitude, position.coords.longitude];
            userPosition = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
            };
            if (userMarker) {
                userMarker.setLatLng(userCoordinates);
            } else {
                userMarker = L.circleMarker(userCoordinates, {
                    radius: 10,
                    color: '#124e78',
                    fillColor: '#2d9cdb',
                    fillOpacity: 1,
                    weight: 4,
                }).addTo(map).bindPopup('Your Location');
            }

            markers.forEach((marker) => {
                if (marker.coordinatePrecision === 'exact') {
                    marker.distanceKm = haversineDistanceKm(
                        position.coords.latitude,
                        position.coords.longitude,
                        marker.latitude,
                        marker.longitude,
                    );
                }
                experienceLayers.get(marker.detailsUrl)?.layer.setPopupContent(createPopup(marker));
            });

            const nearbyMarkers = renderNearbyExperiences();
            const focusedBounds = [userCoordinates, ...nearbyMarkers.map((marker) => [marker.latitude, marker.longitude])];
            map.fitBounds(focusedBounds, { padding: [32, 32], maxZoom: 12 });
            viewAllButton.hidden = false;
            locationStatus.textContent = 'Your location was found. Distances are straight-line estimates.';
            locationButton.disabled = false;
            locationButton.removeAttribute('aria-busy');
        }, (error) => {
            const messages = {
                1: 'Location access was not granted. You can still explore experiences on the map.',
                2: 'Your current location could not be determined.',
                3: 'The location request timed out. Please try again.',
            };
            showLocationError(messages[error.code] ?? 'Your current location could not be determined.');
        }, {
            enableHighAccuracy: false,
            timeout: 10000,
            maximumAge: 300000,
        });
    });
});
