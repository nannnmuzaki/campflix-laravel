<?php
namespace Database\Factories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

class FilmFactory extends Factory
{
    public function definition(): array
    {
        return [
            'judul' => fake()->unique()->sentence(3),
            'sutradara' => fake()->name(),
            'tahun' => fake()->year(),
            // 'description' => fake()->paragraph(5), // Menambahkan deskripsi
            // 'poster_url' => 'https://via.placeholder.com/400x600?text=' . urlencode(fake()->words(2, true)), // Poster placeholder
            // 'duration_minutes' => fake()->numberBetween(90, 180), // Durasi film
            'genre_id' => Genre::inRandomOrder()->first()->id ?? Genre::factory(), // Ambil ID genre acak
        ];
    }
}