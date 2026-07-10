<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $templates = [
            ['title' => 'Pengingat Pembayaran', 'message' => 'Jangan lupa bayar sewa bulan ini sebelum tanggal jatuh tempo.'],
            ['title' => 'Pembayaran Terverifikasi', 'message' => 'Pembayaran sewa Anda telah diverifikasi oleh admin.'],
            ['title' => 'Pembayaran Ditolak', 'message' => 'Bukti pembayaran Anda ditolak, mohon upload ulang dengan bukti yang valid.'],
            ['title' => 'Kontrak Sewa Berakhir', 'message' => 'Masa sewa Anda akan segera berakhir, silakan hubungi admin untuk perpanjangan.'],
            ['title' => 'Kamar Baru Tersedia', 'message' => 'Ada kamar baru yang tersedia di gedung Anda.'],
        ];

        $pick = $this->faker->randomElement($templates);

        return [
            // user_id WAJIB di-override oleh seeder
            'user_id' => null,
            'title' => $pick['title'],
            'message' => $pick['message'],
            'is_read' => $this->faker->boolean(40),
        ];
    }
}
