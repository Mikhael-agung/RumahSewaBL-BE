<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            // building_id & room_code WAJIB di-override oleh seeder
            // (unique key-nya per building, jadi harus dijamin unik di level seeder)
            'building_id' => null,
            'room_code' => null,
            'monthly_price' => $this->faker->randomElement([850000, 1000000, 1200000, 1500000, 1800000, 2000000, 2500000]),
            'room_status' => $this->faker->randomElement([
                'available', 'available', 'available', // bobot lebih besar
                'occupied', 'occupied', 'occupied', 'occupied', 'occupied',
                'maintenance',
            ]),
            'notes' => $this->faker->optional(0.3)->sentence(6),
        ];
    }
}
