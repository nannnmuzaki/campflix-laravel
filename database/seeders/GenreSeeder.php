<?php
namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = ['Aksi', 'Komedi', 'Horor', 'Drama', 'Sci-Fi', 'Romantis', 'Animasi'];
        foreach ($genres as $genre) {
            Genre::create(['nama_genre' => $genre]);
        }
    }
}