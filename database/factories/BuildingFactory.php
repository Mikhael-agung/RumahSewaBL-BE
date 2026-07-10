<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        $idFaker = \Faker\Factory::create('id_ID');

        $names = ['Kos Melati', 'Kos Anggrek', 'Kos Mawar', 'Kos Dahlia', 'Kos Kenanga', 'Kos Cempaka', 'Kos Flamboyan'];

        return [
            // Prefix beda dari data existing (BLD001, BLD002, DDL01234)
            'building_code' => 'BLDX-' . $this->faker->unique()->numerify('###'),
            'building_name' => $this->faker->randomElement($names) . ' ' . $idFaker->citySuffix(),
            'building_address' => $idFaker->address(),
            'description' => $this->faker->sentence(10),
        ];
    }
}