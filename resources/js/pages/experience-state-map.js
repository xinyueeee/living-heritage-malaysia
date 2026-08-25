const pointInRing = ([longitude, latitude], ring) => {
    let inside = false;

    for (let current = 0, previous = ring.length - 1; current < ring.length; previous = current++) {
        const [currentLongitude, currentLatitude] = ring[current];
        const [previousLongitude, previousLatitude] = ring[previous];
        const crossesLatitude = (currentLatitude > latitude) !== (previousLatitude > latitude);
        const intersectionLongitude = (previousLongitude - currentLongitude)
            * (latitude - currentLatitude)
            / (previousLatitude - currentLatitude)
            + currentLongitude;

        if (crossesLatitude && longitude < intersectionLongitude) {
            inside = !inside;
        }
    }

    return inside;
};

const pointInPolygon = (point, rings) => pointInRing(point, rings[0])
    && !rings.slice(1).some((hole) => pointInRing(point, hole));

export const pointInGeometry = (longitude, latitude, geometry) => {
    if (!geometry || !Number.isFinite(longitude) || !Number.isFinite(latitude)) {
        return false;
    }

    if (geometry.type === 'Polygon') {
        return pointInPolygon([longitude, latitude], geometry.coordinates);
    }

    if (geometry.type === 'MultiPolygon') {
        return geometry.coordinates.some((polygon) => pointInPolygon([longitude, latitude], polygon));
    }

    return false;
};

export const stateForMarker = (marker, features) => features.find((feature) =>
    pointInGeometry(marker.longitude, marker.latitude, feature.geometry)
)?.properties?.state ?? null;

export const markerMatchesSelection = (marker, activeGroups, selectedState) =>
    activeGroups.has(marker.markerGroup)
    && (selectedState === null || marker.stateName === selectedState);

