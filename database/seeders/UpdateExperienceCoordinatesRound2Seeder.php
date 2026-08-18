<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * One-time coordinate update for the 17 approved round-two Tourism Malaysia Experiences.
 *
 * This seeder updates only latitude and longitude on existing records. It
 * never creates or deletes Experiences, and it aborts on any identity or
 * existing-coordinate conflict.
 */
class UpdateExperienceCoordinatesRound2Seeder extends Seeder
{
    public function run(): void
    {
        $counters = ['matched' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0];

        try {
            DB::transaction(function () use (&$counters): void {
                $validated = [];

                // Validate and lock every identity before changing any row.
                foreach ($this->coordinateUpdates() as $target) {
                    $candidates = DB::table('experiences')
                        ->whereDate('start_date', $target['start_date'])
                        ->whereDate('end_date', $target['end_date'])
                        ->lockForUpdate()
                        ->get(['experiences_id', 'experiences_name', 'latitude', 'longitude']);

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
                            'Existing coordinates differ from the review for "%s"; refusing to overwrite them.',
                            $target['experiences_name'],
                        ));
                    }

                    $validated[] = [
                        'experiences_id' => $match->experiences_id,
                        'latitude' => $target['latitude'],
                        'longitude' => $target['longitude'],
                        'already_matches' => ! $latitudeIsNull,
                    ];
                    $counters['matched']++;
                }

                foreach ($validated as $target) {
                    if ($target['already_matches']) {
                        $counters['unchanged']++;
                        continue;
                    }

                    $affected = DB::table('experiences')
                        ->where('experiences_id', $target['experiences_id'])
                        ->whereNull('latitude')
                        ->whereNull('longitude')
                        ->update([
                            'latitude' => $target['latitude'],
                            'longitude' => $target['longitude'],
                        ]);

                    if ($affected !== 1) {
                        throw new RuntimeException(sprintf(
                            'Coordinate update safety check failed for Experience ID %s.',
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
     * Coordinates copied exactly from the READY/HIGH-confidence rows in
     * docs/tourism-malaysia-coordinate-review-round2.csv.
     *
     * @return array<int, array{experiences_name: string, start_date: string, end_date: string, latitude: string, longitude: string}>
     */
    private function coordinateUpdates(): array
    {
        return [
            ['experiences_name' => 'Tatreez : Reclaiming Palestine Through Embroidery', 'start_date' => '2026-06-19', 'end_date' => '2027-04-25', 'latitude' => '3.1414525', 'longitude' => '101.6898711'],
            ['experiences_name' => 'Malaysia Gifts Fair 2026', 'start_date' => '2026-06-30', 'end_date' => '2026-07-02', 'latitude' => '3.1543230', 'longitude' => '101.7126043'],
            ['experiences_name' => 'Malaysia Durian Festival 2026', 'start_date' => '2026-07-04', 'end_date' => '2026-07-05', 'latitude' => '3.1567873', 'longitude' => '101.7073427'],
            ['experiences_name' => 'Muallim Cross Country Run 2026', 'start_date' => '2026-07-05', 'end_date' => '2026-07-05', 'latitude' => '3.7385900', 'longitude' => '101.5422600'],
            ['experiences_name' => 'Ipoh Half Marathon 2026', 'start_date' => '2026-07-12', 'end_date' => '2026-07-12', 'latitude' => '4.6087600', 'longitude' => '101.1019500'],
            ['experiences_name' => 'Cameron Ultra Race 2026', 'start_date' => '2026-07-17', 'end_date' => '2026-07-19', 'latitude' => '4.4707980', 'longitude' => '101.3819420'],
            ['experiences_name' => 'Festival Kraf Utara 2026', 'start_date' => '2026-07-23', 'end_date' => '2026-08-09', 'latitude' => '6.3954410', 'longitude' => '100.1690654'],
            ['experiences_name' => 'Tampin Trans Naning Ultra', 'start_date' => '2026-07-24', 'end_date' => '2026-07-26', 'latitude' => '2.4854924', 'longitude' => '102.2229458'],
            ['experiences_name' => 'Pesta Kuantan 188', 'start_date' => '2026-07-31', 'end_date' => '2026-08-02', 'latitude' => '3.8039673', 'longitude' => '103.3274856'],
            ['experiences_name' => 'Art Of Speed', 'start_date' => '2026-08-01', 'end_date' => '2026-08-02', 'latitude' => '2.9787684', 'longitude' => '101.6960130'],
            ['experiences_name' => 'Malaysia Marathon 2026', 'start_date' => '2026-08-30', 'end_date' => '2026-08-30', 'latitude' => '3.1491540', 'longitude' => '101.7129531'],
            ['experiences_name' => 'Sepilok Jazz Festival', 'start_date' => '2026-09-04', 'end_date' => '2026-09-05', 'latitude' => '5.8764506', 'longitude' => '117.9445272'],
            ['experiences_name' => 'RHB Lekas Highway Ride 2026', 'start_date' => '2026-09-12', 'end_date' => '2026-09-12', 'latitude' => '2.9571167', 'longitude' => '101.8262000'],
            ['experiences_name' => 'VM2026 Food Festival @ MATIC', 'start_date' => '2026-09-25', 'end_date' => '2026-09-27', 'latitude' => '3.1567873', 'longitude' => '101.7073427'],
            ['experiences_name' => 'Ironman 70.3 Langkawi', 'start_date' => '2026-11-21', 'end_date' => '2026-11-21', 'latitude' => '6.3678172', 'longitude' => '99.6814526'],
            ['experiences_name' => 'Sibu Bike Week', 'start_date' => '2026-12-05', 'end_date' => '2026-12-06', 'latitude' => '2.2923361', 'longitude' => '111.8215944'],
            ['experiences_name' => 'Nakawan Ultra 3.0', 'start_date' => '2026-12-11', 'end_date' => '2026-12-12', 'latitude' => '6.5378978', 'longitude' => '100.1686057'],
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
            'Experience Coordinates Round 2: matched=%d, updated=%d, unchanged=%d, failed=%d, transaction=%s.',
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
