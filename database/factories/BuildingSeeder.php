<?php

namespace Database\Seeders;

use App\Models\Building;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    public function run(): void
    {
        Building::factory()->count(3)->create();

        $this->command->info('BuildingSeeder: 3 building baru dibuat.');
    }
}
