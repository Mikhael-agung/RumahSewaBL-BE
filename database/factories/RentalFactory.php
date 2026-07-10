<?php

namespace Database\Factories;

use App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentalFactory extends Factory
{
    protected $model = Rental::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-10 months', '-1 month');

        return [
            // Prefix beda dari data existing biar gak nabrak rental_code lama
            'rental_code' => 'RNTX-' . $this->faker->unique()->numerify('#####'),
            // tenant_id, room_id, rental_status, start_date, end_date, created_by
            // di-override oleh seeder (butuh kontrol supaya rooms/status konsisten)
            'tenant_id' => null,
            'room_id' => null,
            'start_date' => $start,
            'end_date' => null,
            'rental_status' => 'active', // pakai nilai Inggris, konsisten sama StoreRentalRequest
            'created_by' => null,
        ];
    }
}
