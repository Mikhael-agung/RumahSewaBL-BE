<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            TenantSeeder::class,
            BuildingSeeder::class,
            RoomSeeder::class,
            RentalSeeder::class,
            PaymentDeadlineSeeder::class,
            PaymentSeeder::class,
            NotificationSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}   