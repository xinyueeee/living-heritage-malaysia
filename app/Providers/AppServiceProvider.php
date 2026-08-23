<?php

namespace App\Providers;

use App\Repositories\Contracts\DiscoveryActivityRepositoryInterface;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use App\Repositories\Eloquent\EloquentDiscoveryActivityRepository;
use App\Repositories\Eloquent\EloquentExperienceRepository;
use App\Services\Experience\Contracts\DiscoveryIntentParserInterface;
use App\Services\Experience\FallbackDiscoveryIntentParser;
use App\View\Composers\HeaderComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            DiscoveryActivityRepositoryInterface::class,
            EloquentDiscoveryActivityRepository::class
        );

        $this->app->bind(
            ExperienceRepositoryInterface::class,
            EloquentExperienceRepository::class
        );

        $this->app->bind(
            DiscoveryIntentParserInterface::class,
            FallbackDiscoveryIntentParser::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('partials.header', HeaderComposer::class);
    }
}
