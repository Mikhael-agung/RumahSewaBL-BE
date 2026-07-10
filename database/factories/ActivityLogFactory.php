<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        $types = [
            'login' => 'User berhasil login ke sistem.',
            'update_profile' => 'User memperbarui data profil.',
            'create_payment' => 'User mengunggah bukti pembayaran baru.',
            'verify_payment' => 'Admin/manager memverifikasi pembayaran.',
            'reject_payment' => 'Admin/manager menolak pembayaran.',
            'create_rental' => 'Rental baru dibuat oleh admin/manager.',
            'update_room' => 'Data kamar diperbarui.',
        ];

        $type = $this->faker->randomElement(array_keys($types));

        return [
            // user_id WAJIB di-override oleh seeder
            'user_id' => null,
            'activity_type' => $type,
            'activity_description' => $types[$type],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}
