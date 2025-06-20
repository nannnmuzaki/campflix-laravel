<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Buat 1 user admin
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@campflix.test',
        ]);

        // Buat 10 user biasa
        User::factory(10)->create();
    }
}