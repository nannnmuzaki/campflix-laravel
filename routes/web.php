<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'index')->name('home');

Volt::route('/jadwal/{jadwal}', 'jadwal-show')->name('jadwal.show');

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('admin', fn () => redirect()->route('admin.films.index'))->name('admin.index');

    Volt::route('admin/films', 'admin.films.index')->name('admin.films.index');
    Volt::route('admin/films/create', 'admin.films.create')->name('admin.films.create');
    Volt::route('admin/{film}/edit', 'admin.films.edit')->name('admin.films.edit');
    
    Volt::route('admin/jadwal', 'admin.jadwal.index')->name('admin.jadwal.index');
    Volt::route('admin/jadwal/create', 'admin.jadwal.create')->name('admin.jadwal.create');
    Volt::route('admin/jadwal/{jadwal}/edit', 'admin.jadwal.edit')->name('admin.jadwal.edit');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    // Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
