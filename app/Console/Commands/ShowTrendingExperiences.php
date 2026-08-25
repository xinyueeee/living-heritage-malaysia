<?php

namespace App\Console\Commands;

use App\Services\Experience\TrendingExperienceService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ShowTrendingExperiences extends Command
{
    protected $signature = 'experiences:trending
        {--days=7 : Rolling UTC lookback period in days}
        {--limit=10 : Maximum number of Experiences to display}';

    protected $description = 'Display the read-only global Experience ranking by recent meaningful views';

    public function handle(TrendingExperienceService $trending): int
    {
        try {
            $days = $this->integerOption('days');
            $limit = $this->integerOption('limit');
            $experiences = $trending->getTrendingExperiences($days, $limit);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        $this->components->info("Trending Experiences — Last {$days} Days");

        if ($experiences->isEmpty()) {
            $this->components->warn('No eligible Experiences received meaningful views during this period.');

            return self::SUCCESS;
        }

        $this->table(
            ['Rank', 'Experience', 'Views', 'Most recent view (UTC)'],
            $experiences->values()->map(fn ($experience, int $index): array => [
                $index + 1,
                $experience->experiences_name,
                $experience->meaningful_view_count,
                $experience->most_recent_view_at->format('Y-m-d H:i:s'),
            ])->all(),
        );

        return self::SUCCESS;
    }

    private function integerOption(string $name): int
    {
        $value = $this->option($name);

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("The --{$name} option must be an integer.");
        }

        return (int) $value;
    }
}
