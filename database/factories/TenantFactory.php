<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $idFaker = \Faker\Factory::create('id_ID');

        return [
            // Prefix distinct dari data existing (tnt0005, tnt001, dst) biar gak nabrak
            'tenant_code' => 'TNT-' . $this->faker->unique()->numerify('####'),
            // user_id WAJIB di-override oleh seeder (1 user cuma boleh 1 tenant)
            'user_id' => null,
            'full_name' => $idFaker->name(),
            'phone_number' => '08' . $this->faker->numerify('##########'),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
