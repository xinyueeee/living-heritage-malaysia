<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class FestivalCategorySeeder extends Seeder
{
    private const CATEGORY_NAMES = [
        'Cultural Festival',
        'Food Festival',
        'Music Festival',
        'National Celebration',
        'Nature Festival',
        'Sports Festival',
    ];

    public function run(): void
    {
        $counters = ['inserted' => 0, 'existing' => 0, 'failed' => 0];

        try {
            DB::transaction(function () use (&$counters): void {
                $festivalType = DB::table('experience_type')
                    ->where('type_name', 'Festival')
                    ->first(['type_id']);

                if ($festivalType === null) {
                    throw new RuntimeException(
                        'Festival category import aborted: ExperienceType "Festival" was not found.'
                    );
                }

                foreach (self::CATEGORY_NAMES as $categoryName) {
                    $exists = DB::table('category')
                        ->where('category_name', $categoryName)
                        ->where('type_id', $festivalType->type_id)
                        ->exists();

                    if ($exists) {
                        $counters['existing']++;

                        continue;
                    }

                    $inserted = DB::table('category')->insert([
                        'category_name' => $categoryName,
                        'type_id' => $festivalType->type_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if (! $inserted) {
                        throw new RuntimeException(sprintf(
                            'Festival category import failed while inserting "%s".',
                            $categoryName,
                        ));
                    }

                    $counters['inserted']++;
                }
            });
        } catch (Throwable $exception) {
            // The transaction has rolled back, so no attempted category inserts remain.
            $counters['inserted'] = 0;
            $counters['failed']++;
            $this->printSummary($counters, true);

            throw $exception;
        }

        $this->printSummary($counters, false);
    }

    /** @param array{inserted: int, existing: int, failed: int} $counters */
    private function printSummary(array $counters, bool $rolledBack): void
    {
        $message = sprintf(
            'Festival Category Seeder: inserted=%d, already_existing=%d, failed=%d, transaction=%s.',
            $counters['inserted'],
            $counters['existing'],
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
