import test from 'node:test';
import assert from 'node:assert/strict';
import {
    eligibleNearbyMarkers,
    NEARBY_BATCH_SIZE,
    sortNearbyMarkers,
    visibleNearbyMarkers,
} from '../../resources/js/pages/experience-nearby.js';

const markers = Array.from({ length: 12 }, (_, index) => ({
    id: index + 1,
    distanceKm: 12 - index,
    typeName: index % 2 ? 'Festival' : 'Cultural Experience',
    startDateSort: index % 2 ? `2026-09-${String(20 - index).padStart(2, '0')}` : null,
}));

test('Nearby defaults to deterministic nearest-first ordering for both types', () => {
    const sorted = sortNearbyMarkers(markers);
    assert.deepEqual(sorted.map((marker) => marker.distanceKm), [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);
    assert.deepEqual(new Set(sorted.map((marker) => marker.typeName)), new Set(['Festival', 'Cultural Experience']));
});

test('initial results and repeated View More batches reveal all markers', () => {
    assert.equal(visibleNearbyMarkers(markers, NEARBY_BATCH_SIZE).length, 5);
    assert.equal(visibleNearbyMarkers(markers, 10).length, 10);
    assert.equal(visibleNearbyMarkers(markers, 15).length, 12);
    assert.equal(visibleNearbyMarkers(markers, NEARBY_BATCH_SIZE).length, 5);
});

test('Soonest Date puts dated Festivals first and leaves cultural dates null', () => {
    const sorted = sortNearbyMarkers(markers, 'soonest');
    const datedCount = markers.filter((marker) => marker.startDateSort).length;
    assert.ok(sorted.slice(0, datedCount).every((marker) => marker.typeName === 'Festival'));
    assert.ok(sorted.slice(datedCount).every((marker) => marker.startDateSort === null));
    assert.deepEqual(
        sorted.slice(datedCount).map((marker) => marker.distanceKm),
        [2, 4, 6, 8, 10, 12],
    );
});

test('existing radius behavior is preserved, including nearest fallback', () => {
    assert.deepEqual(eligibleNearbyMarkers(markers, 5), { markers: markers.slice(7), usesRadius: true });
    assert.deepEqual(eligibleNearbyMarkers(markers, 0.5), { markers, usesRadius: false });
});
