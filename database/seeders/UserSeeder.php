<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Password semua: password123 (di-hash otomatis via UserFactory::$password)

        $adminCreated = false;
        if (! User::where('username', 'admin')->exists()) {
            User::factory()->administrator()->create(['username' => 'admin']);
            $adminCreated = true;
        }

        $managerUsernames = ['manager1', 'manager2'];
        $managersCreated = 0;

        foreach ($managerUsernames as $username) {
            if (User::where('username', $username)->exists()) {
                continue;
            }

            User::factory()->manager()->create(['username' => $username]);
            $managersCreated++;
        }

        $this->command->info('UserSeeder: admin ' . ($adminCreated ? 'dibuat' : 'sudah ada, skip') . ", {$managersCreated} manager baru dibuat.");
    }
}