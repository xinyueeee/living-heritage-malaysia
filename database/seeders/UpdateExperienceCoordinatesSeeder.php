<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * One-time coordinate update for reviewed Tourism Malaysia Experiences.
 *
 * This seeder updates only latitude and longitude on 47 existing records. It
 * never creates or deletes Experiences, and it aborts on any identity or
 * existing-coordinate conflict.
 */
class UpdateExperienceCoordinatesSeeder extends Seeder
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
     * docs/tourism-malaysia-coordinate-review.csv.
     *
     * @return array<int, array{experiences_name: string, start_date: string, end_date: string, latitude: string, longitude: string}>
     */
    private function coordinateUpdates(): array
    {
        return [
            ['experiences_name' => 'Kinabalu Sky Fest 2026', 'start_date' => '2026-07-04', 'end_date' => '2026-07-04', 'latitude' => '6.0371509', 'longitude' => '116.1201875'],
            ['experiences_name' => 'I Food Expo', 'start_date' => '2026-07-10', 'end_date' => '2026-07-12', 'latitude' => '3.051063', 'longitude' => '101.6705112'],
            ['experiences_name' => 'Asian Youth Festival 2026', 'start_date' => '2026-07-10', 'end_date' => '2026-07-12', 'latitude' => '3.0294634', 'longitude' => '101.7176331'],
            ['experiences_name' => 'Miss SHOPhia Shopping Hunt 2026', 'start_date' => '2026-07-11', 'end_date' => '2026-07-11', 'latitude' => '1.5816546', 'longitude' => '110.3779046'],
            ['experiences_name' => 'Royal Selangor Jazz Festival 2026', 'start_date' => '2026-07-12', 'end_date' => '2026-07-12', 'latitude' => '3.195917', 'longitude' => '101.7246161'],
            ['experiences_name' => 'The Malaysian International Food & Beverage Trade Fair', 'start_date' => '2026-07-15', 'end_date' => '2026-07-17', 'latitude' => '3.154323', 'longitude' => '101.7126043'],
            ['experiences_name' => 'Penang Hill Festival 2026', 'start_date' => '2026-07-17', 'end_date' => '2026-07-19', 'latitude' => '5.4246532', 'longitude' => '100.2688869'],
            ['experiences_name' => 'Metropolitan Rhythms: The Emigré and the American', 'start_date' => '2026-07-18', 'end_date' => '2026-07-18', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'Festival Udara Taiping 2026', 'start_date' => '2026-07-23', 'end_date' => '2026-07-26', 'latitude' => '4.8634152', 'longitude' => '100.7166908'],
            ['experiences_name' => 'Taiping Air Festival', 'start_date' => '2026-07-23', 'end_date' => '2026-07-26', 'latitude' => '4.8634152', 'longitude' => '100.7166908'],
            ['experiences_name' => 'Citrawarna 2026', 'start_date' => '2026-07-24', 'end_date' => '2026-07-26', 'latitude' => '3.1487691', 'longitude' => '101.6936377'],
            ['experiences_name' => 'Lenggong Outdoor Festival', 'start_date' => '2026-07-24', 'end_date' => '2026-07-26', 'latitude' => '5.00107', 'longitude' => '100.9480721'],
            ['experiences_name' => 'Ironman 70.3 Desaru Coast', 'start_date' => '2026-07-24', 'end_date' => '2026-07-26', 'latitude' => '1.536381', 'longitude' => '104.2623299'],
            ['experiences_name' => 'Temasya Orang Kedah', 'start_date' => '2026-07-30', 'end_date' => '2026-08-02', 'latitude' => '6.1359146', 'longitude' => '100.3711086'],
            ['experiences_name' => 'Youth Performance Speed Fest', 'start_date' => '2026-08-07', 'end_date' => '2026-08-09', 'latitude' => '1.4240029', 'longitude' => '103.6623872'],
            ['experiences_name' => 'Taiping Half Marathon', 'start_date' => '2026-08-09', 'end_date' => '2026-08-09', 'latitude' => '4.8542943', 'longitude' => '100.7432939'],
            ['experiences_name' => 'Penang Hill Heritage Forest Challenge 2026 (PHHFC2026)', 'start_date' => '2026-08-16', 'end_date' => '2026-08-16', 'latitude' => '5.4246532', 'longitude' => '100.2688869'],
            ['experiences_name' => 'Pahang Eco 2026', 'start_date' => '2026-08-21', 'end_date' => '2026-08-23', 'latitude' => '3.8126555', 'longitude' => '103.3715246'],
            ['experiences_name' => 'A Heart Unveiled: The Music of Tchaikovsky', 'start_date' => '2026-08-22', 'end_date' => '2026-08-22', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'The Sky Race', 'start_date' => '2026-08-22', 'end_date' => '2026-08-23', 'latitude' => '3.1416214', 'longitude' => '101.7006856'],
            ['experiences_name' => 'Kodaline – Farewell Tour', 'start_date' => '2026-08-26', 'end_date' => '2026-08-26', 'latitude' => '3.1304759', 'longitude' => '101.6267541'],
            ['experiences_name' => 'A Knight\'s Tale: Valor and Romance in Music', 'start_date' => '2026-08-29', 'end_date' => '2026-08-29', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'Bukit Maras Trail Run Challenge 2.0', 'start_date' => '2026-08-29', 'end_date' => '2026-08-29', 'latitude' => '5.4224486', 'longitude' => '103.0206093'],
            ['experiences_name' => 'A Tribute to Alfonso Soliano', 'start_date' => '2026-09-02', 'end_date' => '2026-09-02', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'Yin and Yang: A Dance Kaleidoscope', 'start_date' => '2026-09-05', 'end_date' => '2026-09-05', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'PJ Half Marathon', 'start_date' => '2026-09-06', 'end_date' => '2026-09-06', 'latitude' => '3.0979864', 'longitude' => '101.6444066'],
            ['experiences_name' => 'Malaysia Ultra-Trail By Utmb', 'start_date' => '2026-09-10', 'end_date' => '2026-09-13', 'latitude' => '4.8542943', 'longitude' => '100.7432939'],
            ['experiences_name' => 'Maurice Steger\'s Nature Concerti', 'start_date' => '2026-09-12', 'end_date' => '2026-09-12', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'Powerman Malaysia 2026', 'start_date' => '2026-09-18', 'end_date' => '2026-09-20', 'latitude' => '2.9352614', 'longitude' => '101.6911402'],
            ['experiences_name' => 'Malaysia Sarong Music Run 2026', 'start_date' => '2026-09-19', 'end_date' => '2026-09-19', 'latitude' => '3.1591628', 'longitude' => '101.7133606'],
            ['experiences_name' => 'Malaysia International Craft Fair', 'start_date' => '2026-09-24', 'end_date' => '2026-10-05', 'latitude' => '3.1492055', 'longitude' => '101.7188848'],
            ['experiences_name' => 'Three By Three', 'start_date' => '2026-10-03', 'end_date' => '2026-10-03', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'The Music of Queen…Lives On!', 'start_date' => '2026-10-10', 'end_date' => '2026-10-10', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'Jaclyn Victor Gemilang Bersama MPO', 'start_date' => '2026-10-17', 'end_date' => '2026-10-17', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'Sarawak International Dragon Boat Regatta 2026', 'start_date' => '2026-10-24', 'end_date' => '2026-10-26', 'latitude' => '1.5591838', 'longitude' => '110.3467935'],
            ['experiences_name' => 'A Regal Evening with Stephen Hough', 'start_date' => '2026-10-24', 'end_date' => '2026-10-24', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'Petronas Grand Prix of Malaysia (MotoGP)', 'start_date' => '2026-10-30', 'end_date' => '2026-11-01', 'latitude' => '2.7602187', 'longitude' => '101.7368758'],
            ['experiences_name' => 'Beats of Borneo: Alena Murang with the MPO', 'start_date' => '2026-10-31', 'end_date' => '2026-10-31', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'Selangor Marathon 2026', 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'latitude' => '2.9289522', 'longitude' => '101.6519515'],
            ['experiences_name' => 'LANY : Soft World Tour', 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'latitude' => '3.0537311', 'longitude' => '101.6934618'],
            ['experiences_name' => 'As If She Were Here: Chen Jia Sings Teresa Teng', 'start_date' => '2026-11-07', 'end_date' => '2026-11-29', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'Simfoni Mantra: Kunto Aji bersama MPO', 'start_date' => '2026-11-14', 'end_date' => '2026-11-14', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'Wave to Earth', 'start_date' => '2026-11-22', 'end_date' => '2026-11-22', 'latitude' => '3.1304759', 'longitude' => '101.6267541'],
            ['experiences_name' => 'Penang International Dragon Boat Regatta 2026', 'start_date' => '2026-11-27', 'end_date' => '2026-11-29', 'latitude' => '5.4578567', 'longitude' => '100.3131405'],
            ['experiences_name' => 'A Chorale Spectacular', 'start_date' => '2026-12-05', 'end_date' => '2026-12-05', 'latitude' => '3.1580219', 'longitude' => '101.711686'],
            ['experiences_name' => 'Penang Bridge International Marathon', 'start_date' => '2026-12-13', 'end_date' => '2026-12-13', 'latitude' => '5.2614822', 'longitude' => '100.4363541'],
            ['experiences_name' => 'BTS World Tour ‘Arirang’ in Kuala Lumpur', 'start_date' => '2026-12-13', 'end_date' => '2026-12-13', 'latitude' => '3.0546755', 'longitude' => '101.691369'],
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
            'Experience Coordinates: matched=%d, updated=%d, unchanged=%d, failed=%d, transaction=%s.',
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
