<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * One-time correction for five verified Tourism Malaysia Experience images.
 *
 * This seeder only updates image_url on existing, uniquely matched records.
 * It never creates or deletes Experience records.
 */
class UpdateMissingExperienceImagesSeeder extends Seeder
{
    public function run(): void
    {
        $counters = [
            'updated' => 0,
            'unchanged' => 0,
            'failed' => 0,
        ];

        try {
            DB::transaction(function () use (&$counters): void {
                foreach ($this->imageCorrections() as $correction) {
                    $candidates = DB::table('experiences')
                        ->whereDate('start_date', $correction['start_date'])
                        ->whereDate('end_date', $correction['end_date'])
                        ->get(['experiences_id', 'experiences_name']);

                    $matches = $candidates->filter(function (object $candidate) use ($correction): bool {
                        return $this->normalize($candidate->experiences_name)
                            === $this->normalize($correction['experiences_name']);
                    })->values();

                    if ($matches->count() !== 1) {
                        throw new RuntimeException(sprintf(
                            'Expected one Experience match for image correction "%s" (%s to %s); found %d.',
                            $correction['experiences_name'],
                            $correction['start_date'],
                            $correction['end_date'],
                            $matches->count(),
                        ));
                    }

                    $affected = DB::table('experiences')
                        ->where('experiences_id', $matches->first()->experiences_id)
                        ->update(['image_url' => $correction['image_url']]);

                    if ($affected === 1) {
                        $counters['updated']++;
                    } else {
                        $counters['unchanged']++;
                    }
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
     *     image_url: string
     * }>
     */
    private function imageCorrections(): array
    {
        return [
            [
                'experiences_name' => "A Knight's Tale: Valor and Romance in Music",
                'start_date' => '2026-08-29',
                'end_date' => '2026-08-29',
                'image_url' => 'https://www.malaysia.travel/storage/11864/conversions/481fa90fdeca9cf693b6a616aa40ebcb-large.jpg',
            ],
            [
                'experiences_name' => 'Merdeka Day',
                'start_date' => '2026-08-31',
                'end_date' => '2026-08-31',
                'image_url' => 'https://www.malaysia.travel/storage/10949/conversions/2f94c421f0f9ff6aa4549b23c6c6db4f-large.jpg',
            ],
            [
                'experiences_name' => "Maurice Steger's Nature Concerti",
                'start_date' => '2026-09-12',
                'end_date' => '2026-09-12',
                'image_url' => 'https://www.malaysia.travel/storage/11856/conversions/03ea1edf40f8e9e0589479e19ed132c9-large.jpg',
            ],
            [
                'experiences_name' => 'VM2026 Food Festival @ MATIC',
                'start_date' => '2026-09-25',
                'end_date' => '2026-09-27',
                'image_url' => 'https://www.malaysia.travel/storage/13211/conversions/235536ea5a3ac438f82f6abd40d21921-large.jpg',
            ],
            [
                'experiences_name' => 'Christmas Celebration',
                'start_date' => '2026-12-25',
                'end_date' => '2026-12-25',
                'image_url' => 'https://www.malaysia.travel/storage/11524/conversions/0f247eb435a985152d28f110a9c67e8d-large.jpg',
            ],
        ];
    }

    private function normalize(?string $value): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', trim($value ?? '')) ?? '');
    }

    /** @param array{updated: int, unchanged: int, failed: int} $counters */
    private function printSummary(array $counters, bool $rolledBack): void
    {
        $message = sprintf(
            'Missing Experience Images: updated=%d, unchanged=%d, failed=%d, transaction=%s.',
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
