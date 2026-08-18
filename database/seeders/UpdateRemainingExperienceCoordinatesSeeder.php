<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * One-time coordinate correction for the four final reviewed Tourism Malaysia Experiences.
 *
 * This seeder never creates or deletes Experiences. It updates only latitude and
 * longitude, plus the specifically reviewed KOKOL Ultra location_name correction.
 */
class UpdateRemainingExperienceCoordinatesSeeder extends Seeder
{
    public function run(): void
    {
        $counters = ['matched' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0];

        try {
            DB::transaction(function () use (&$counters): void {
                $validated = [];

                // Validate and lock all four identities before changing any row.
                foreach ($this->coordinateUpdates() as $target) {
                    $candidates = DB::table('experiences')
                        ->whereDate('start_date', $target['start_date'])
                        ->whereDate('end_date', $target['end_date'])
                        ->lockForUpdate()
                        ->get(['experiences_id', 'experiences_name', 'location_name', 'latitude', 'longitude']);

                    $matches = $candidates->filter(fn (object $candidate): bool =>
                        $this->normalize($candidate->experiences_name) === $this->normalize($target['experiences_name'])
                    )->values();

                    if ($matches->count() !== 1) {
                        throw new RuntimeException(sprintf(
                            'Expected one Experience match for "%s" (%s to %s); found %d.',
                            $target['experiences_name'],
                            $target['start_date'],
                            $target['end_date'],
                            $matches->count(),
                        ));
                    }

                    $match = $matches->first();
                    $latitudeIsNull = $match->latitude === null;
                    $longitudeIsNull = $match->longitude === null;

                    if ($latitudeIsNull !== $longitudeIsNull) {
                        throw new RuntimeException(sprintf(
                            'Existing coordinates are incomplete for "%s"; refusing to overwrite them.',
                            $target['experiences_name'],
                        ));
                    }

                    if (! $latitudeIsNull && (
                        $this->coordinate($match->latitude) !== $this->coordinate($target['latitude'])
                        || $this->coordinate($match->longitude) !== $this->coordinate($target['longitude'])
                    )) {
                        throw new RuntimeException(sprintf(
                            'Existing coordinates differ from the final review for "%s"; refusing to overwrite them.',
                            $target['experiences_name'],
                        ));
                    }

                    $locationNeedsUpdate = false;
                    if ($target['reviewed_location_name'] !== null) {
                        if ($match->location_name === $target['reviewed_location_name']) {
                            $locationNeedsUpdate = false;
                        } elseif ($match->location_name === $target['expected_location_name']) {
                            $locationNeedsUpdate = true;
                        } else {
                            throw new RuntimeException(sprintf(
                                'Existing location_name for "%s" is neither the reviewed old nor new value; refusing to overwrite it.',
                                $target['experiences_name'],
                            ));
                        }
                    }

                    $validated[] = [
                        'experiences_id' => $match->experiences_id,
                        'latitude' => $target['latitude'],
                        'longitude' => $target['longitude'],
                        'coordinates_need_update' => $latitudeIsNull,
                        'expected_location_name' => $target['expected_location_name'],
                        'reviewed_location_name' => $target['reviewed_location_name'],
                        'location_needs_update' => $locationNeedsUpdate,
                    ];
                    $counters['matched']++;
                }

                foreach ($validated as $target) {
                    $updates = [];
                    $query = DB::table('experiences')->where('experiences_id', $target['experiences_id']);

                    if ($target['coordinates_need_update']) {
                        $query->whereNull('latitude')->whereNull('longitude');
                        $updates['latitude'] = $target['latitude'];
                        $updates['longitude'] = $target['longitude'];
                    }

                    if ($target['location_needs_update']) {
                        $query->where('location_name', $target['expected_location_name']);
                        $updates['location_name'] = $target['reviewed_location_name'];
                    }

                    if ($updates === []) {
                        $counters['unchanged']++;
                        continue;
                    }

                    if ($query->update($updates) !== 1) {
                        throw new RuntimeException(sprintf(
                            'Final coordinate update safety check failed for Experience ID %s.',
                            $target['experiences_id'],
                        ));
                    }

                    $counters['updated']++;
                }
            });
        } catch (Throwable $exception) {
            $counters['updated'] = 0;
            $counters['failed']++;
            $this->printSummary($counters, true);

            throw $exception;
        }

        $this->printSummary($counters, false);
    }

    /**
     * @return array<int, array{
     *     experiences_name: string,
     *     start_date: string,
     *     end_date: string,
     *     latitude: string,
     *     longitude: string,
     *     expected_location_name: ?string,
     *     reviewed_location_name: ?string
     * }>
     */
    private function coordinateUpdates(): array
    {
        return [
            ['experiences_name' => 'Pesta Sebauh 2026', 'start_date' => '2026-07-03', 'end_date' => '2026-07-12', 'latitude' => '3.1089346', 'longitude' => '113.2692428', 'expected_location_name' => null, 'reviewed_location_name' => null],
            ['experiences_name' => 'Malaysia International Film Festival', 'start_date' => '2026-07-18', 'end_date' => '2026-07-25', 'latitude' => '3.1346611', 'longitude' => '101.7230194', 'expected_location_name' => null, 'reviewed_location_name' => null],
            ['experiences_name' => 'Challenge Malaysia', 'start_date' => '2026-10-17', 'end_date' => '2026-10-18', 'latitude' => '1.3380057', 'longitude' => '103.5868311', 'expected_location_name' => null, 'reviewed_location_name' => null],
            ['experiences_name' => 'KOKOL Ultra 2026', 'start_date' => '2026-10-23', 'end_date' => '2026-10-25', 'latitude' => '6.0301847', 'longitude' => '116.1580498', 'expected_location_name' => 'Expertise Event Management', 'reviewed_location_name' => 'Padang Pekan Manggatal, Sabah'],
        ];
    }

    private function normalize(?string $value): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', trim($value ?? '')) ?? '');
    }

    private function coordinate(string|float|int $value): string
    {
        return number_format((float) $value, 8, '.', '');
    }

    /** @param array{matched: int, updated: int, unchanged: int, failed: int} $counters */
    private function printSummary(array $counters, bool $rolledBack): void
    {
        $message = sprintf(
            'Remaining Experience Coordinates: matched=%d, updated=%d, unchanged=%d, failed=%d, transaction=%s.',
            $counters['matched'],
            $counters['updated'],
            $counters['unchanged'],
            $counters['failed'],
            $rolledBack ? 'rolled back' : 'committed',
        );

        if ($rolledBack) {
            $this->command?->error($message);

            return;
        }

        $this->command?->info($message);
    }
}
