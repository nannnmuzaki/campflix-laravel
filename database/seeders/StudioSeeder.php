<?php
namespace Database\Seeders;

use App\Models\Studio;
use Illuminate\Database\Seeder;

class StudioSeeder extends Seeder
{
    public function run(): void
    {
        Studio::create(['nama_studio' => 'Studio 1 (2D)', 'kapasitas' => 100]);
        Studio::create(['nama_studio' => 'Studio 2 (3D)', 'kapasitas' => 120]);
        Studio::create(['nama_studio' => 'Studio 3', 'kapasitas' => 80]);
        Studio::create(['nama_studio' => 'IMAX', 'kapasitas' => 150]);
    }
}