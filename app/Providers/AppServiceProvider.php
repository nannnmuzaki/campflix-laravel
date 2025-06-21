<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\JadwalTayang;
use App\Observers\JadwalTayangObserver;
use App\Models\User;
use Illuminate\Support\Facades\Gate;


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

        Gate::define('is-admin', function (User $user) {
            return $user->role === 'admin';
        });
    }
}
