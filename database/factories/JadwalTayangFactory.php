<?php
namespace Database\Factories;

use App\Models\Film;
use App\Models\Studio;
use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalTayangFactory extends Factory
{
    public function definition(): array
    {
        // Daftar jam tayang yang realistis
        $jamTayang = ['12:00', '13:30', '14:45', '16:15', '17:30', '19:15', '20:00', '21:30'];

        return [
            'film_id' => Film::inRandomOrder()->first()->id ?? Film::factory(),
            'studio_id' => Studio::inRandomOrder()->first()->id ?? Studio::factory(),
            'tanggal' => fake()->dateTimeBetween('today', '+1 week')->format('Y-m-d'), // Jadwal untuk minggu ini
            'jam' => fake()->randomElement($jamTayang),
        ];
    }
}