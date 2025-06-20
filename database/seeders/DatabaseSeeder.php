<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Panggil seeder dengan urutan yang benar sesuai dependensi
        $this->call([
            UserSeeder::class,      // Buat user dulu
            GenreSeeder::class,     // Data master
            StudioSeeder::class,    // Data master
            FilmSeeder::class,      // Butuh Genre
            JadwalTayangSeeder::class, // Butuh Film dan Studio
        ]);

        // PENTING: Kita tidak perlu memanggil seeder untuk Tiket.
        // Tiket akan dibuat secara otomatis oleh JadwalTayangObserver yang sudah kita buat.
    }
}