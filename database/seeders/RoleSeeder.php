<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Sengaja dilewati/no-op.
        // Data roles sudah fixed & sudah ada di DB testing (Hostinger):
        //   (1, 'administrator'), (2, 'manager'), (3, 'penyewa')
        // Kalau nanti butuh reverse-seed dari nol (misal migrate:fresh tanpa data lama),
        // uncomment blok di bawah ini:
        //
        // \App\Models\Role::firstOrCreate(['id' => 1], ['name' => 'administrator']);
        // \App\Models\Role::firstOrCreate(['id' => 2], ['name' => 'manager']);
        // \App\Models\Role::firstOrCreate(['id' => 3], ['name' => 'penyewa']);
    }
}