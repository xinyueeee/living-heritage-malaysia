export const NEARBY_BATCH_SIZE = 5;

const markerId = (marker) => Number(marker.id) || Number.MAX_SAFE_INTEGER;

const compareDistance = (first, second) =>
    first.distanceKm - second.distanceKm || markerId(first) - markerId(second);

export const sortNearbyMarkers = (markers, sort = 'nearest') => [...markers].sort((first, second) => {
    if (sort === 'soonest') {
        const firstDated = Boolean(first.startDateSort);
        const secondDated = Boolean(second.startDateSort);

        if (firstDated !== secondDated) {
            return firstDated ? -1 : 1;
        }
        if (firstDated && first.startDateSort !== second.startDateSort) {
            return first.startDateSort.localeCompare(second.startDateSort);
        }
    }

    return compareDistance(first, second);
});

export const eligibleNearbyMarkers = (markers, radiusKm) => {
    const withinRadius = markers.filter((marker) => marker.distanceKm <= radiusKm);

    return {
        markers: withinRadius.length > 0 ? withinRadius : markers,
        usesRadius: withinRadius.length > 0,
    };
};

export const visibleNearbyMarkers = (markers, visibleCount) =>
    markers.slice(0, Math.max(NEARBY_BATCH_SIZE, visibleCount));
