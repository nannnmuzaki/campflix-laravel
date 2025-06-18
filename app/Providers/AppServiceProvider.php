<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\JadwalTayang;
use App\Observers\JadwalTayangObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JadwalTayang::observe(JadwalTayangObserver::class);
    }
}
