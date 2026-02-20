<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (!User::query()->where('Username', 'test-user')->exists()) {
            User::factory()->create([
                'Username' => 'test-user',
                'Nama' => 'Test User',
                'Email' => 'test@example.com',
                'Password' => bcrypt('password'),
            ]);
        }

        // Seeder laporan selain user dinonaktifkan karena modul terkait sudah dihapus.
    }
}
