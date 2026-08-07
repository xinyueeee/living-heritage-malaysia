<?php

namespace App\Providers;

use App\Repositories\Contracts\ExperienceRepositoryInterface;
use App\Repositories\Eloquent\EloquentExperienceRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ExperienceRepositoryInterface::class,
            EloquentExperienceRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
