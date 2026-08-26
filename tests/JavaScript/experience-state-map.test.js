import test from 'node:test';
import assert from 'node:assert/strict';
import { markerMatchesSelection, pointInGeometry, stateForMarker } from '../../resources/js/pages/experience-state-map.js';

const features = [
    {
        properties: { state: 'Johor' },
        geometry: { type: 'Polygon', coordinates: [[[102, 1], [104, 1], [104, 3], [102, 3], [102, 1]]] },
    },
    {
        properties: { state: 'Selangor' },
        geometry: { type: 'Polygon', coordinates: [[[100, 3], [102, 3], [102, 5], [100, 5], [100, 3]]] },
    },
];

test('polygon and multipolygon containment classify coordinates conservatively', () => {
    assert.equal(pointInGeometry(103, 2, features[0].geometry), true);
    assert.equal(pointInGeometry(101, 4, features[0].geometry), false);
    assert.equal(pointInGeometry(103, 2, { type: 'MultiPolygon', coordinates: [features[0].geometry.coordinates] }), true);
    assert.equal(pointInGeometry(Number.NaN, 2, features[0].geometry), false);
});

test('Johor selection excludes Selangor and clearing restores both', () => {
    const johor = { longitude: 103, latitude: 2, markerGroup: 'festival' };
    const selangor = { longitude: 101, latitude: 4, markerGroup: 'festival' };
    johor.stateName = stateForMarker(johor, features);
    selangor.stateName = stateForMarker(selangor, features);
    const activeGroups = new Set(['festival']);

    assert.equal(markerMatchesSelection(johor, activeGroups, 'Johor'), true);
    assert.equal(markerMatchesSelection(selangor, activeGroups, 'Johor'), false);
    assert.equal(markerMatchesSelection(johor, activeGroups, null), true);
    assert.equal(markerMatchesSelection(selangor, activeGroups, null), true);
});

test('state and category selection combine without resetting either filter', () => {
    const marker = { stateName: 'Johor', markerGroup: 'festival' };
    assert.equal(markerMatchesSelection(marker, new Set(['festival']), 'Johor'), true);
    assert.equal(markerMatchesSelection(marker, new Set(['heritage']), 'Johor'), false);
});

test('state containment is independent of Festival or Cultural Experience type', () => {
    const festival = { longitude: 103, latitude: 2, markerGroup: 'festival', typeName: 'Festival' };
    const cultural = { longitude: 103.2, latitude: 2.2, markerGroup: 'heritage', typeName: 'Cultural Experience' };
    const outside = { longitude: 101, latitude: 4, markerGroup: 'heritage', typeName: 'Cultural Experience' };
    [festival, cultural, outside].forEach((marker) => { marker.stateName = stateForMarker(marker, features); });
    const activeGroups = new Set(['festival', 'heritage']);

    assert.equal(markerMatchesSelection(festival, activeGroups, 'Johor'), true);
    assert.equal(markerMatchesSelection(cultural, activeGroups, 'Johor'), true);
    assert.equal(markerMatchesSelection(outside, activeGroups, 'Johor'), false);
    assert.equal([festival, cultural, outside].filter((marker) => markerMatchesSelection(marker, activeGroups, null)).length, 3);
});
