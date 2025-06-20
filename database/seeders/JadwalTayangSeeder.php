<?php
namespace Database\Seeders;

use App\Models\JadwalTayang;
use Illuminate\Database\Seeder;

class JadwalTayangSeeder extends Seeder
{
    public function run(): void
    {
        JadwalTayang::factory(100)->create();
    }
}