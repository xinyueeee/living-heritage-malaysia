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
    appendText(content, 'map-popup-description', marker.shortDescription);

    const details = document.createElement('a');
    details.className = 'map-popup-details';
    details.href = marker.detailsUrl;
    details.textContent = 'View Details →';
    content.appendChild(details);
    popup.appendChild(content);

    return popup;
};

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('experience-map');
    if (!container) {
        return;
    }

    const markers = Array.isArray(window.livingHeritageExperienceMarkers)
        ? window.livingHeritageExperienceMarkers.filter((marker) =>
            Number.isFinite(marker.latitude)
            && Number.isFinite(marker.longitude)
            && marker.latitude >= -90
            && marker.latitude <= 90
            && marker.longitude >= -180
            && marker.longitude <= 180
            && !(marker.latitude === 0 && marker.longitude === 0)
        )
        : [];

    const map = L.map(container, { scrollWheelZoom: false }).setView([4.2, 109.5], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    const bounds = [];
    markers.forEach((marker) => {
        const coordinates = [marker.latitude, marker.longitude];
        L.circleMarker(coordinates, {
            radius: 8,
            color: '#7B1E14',
            fillColor: '#A52A1A',
            fillOpacity: 0.88,
            weight: 2,
        })
            .addTo(map)
            .bindPopup(createPopup(marker), { maxWidth: 300 });
        bounds.push(coordinates);
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [28, 28], maxZoom: 11 });
    }
});
