<?php

namespace Database\Seeders;

use App\Enums\Role;
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
        // Akun super admin awal. Segera ganti password setelah login pertama.
        User::firstOrCreate(['email' => 'admin@impeesa.test'], [
            'name' => 'Super Admin Impeesa',
            'nickname' => 'Admin',
            'password' => 'password',
            'role' => Role::SuperAdmin,
            'is_active' => true,
            'position' => 'Super Admin',
            'member_number' => 'IMP-0001',
            'joined_on' => now(),
        ]);

        $this->call(ImpeesaMemberSeeder::class);
    }
}
