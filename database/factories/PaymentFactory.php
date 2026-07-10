<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $method = $this->faker->randomElement(['upload', 'upload', 'upload', 'manual']);

        return [
            // Prefix beda dari data existing
            'payment_code' => 'PAYX-' . $this->faker->unique()->bothify('#####??'),
            // rental_id, payment_month, payment_year, amount, payment_status
            // di-override oleh seeder (perlu kontrol unique_month_key)
            'rental_id' => null,
            'payment_month' => null,
            'payment_year' => null,
            'amount' => null,
            'payment_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'payment_method' => $method,
            'payment_status' => 'terverifikasi', // default, di-override seeder sesuai skenario
            // Path dummy string aja, tidak ada file asli di storage
            'proof_file_name' => $method === 'upload' ? 'bukti-transfer-' . $this->faker->numerify('####') . '.jpg' : null,
            'proof_file_path' => $method === 'upload' ? 'payment-proofs/dummy-' . $this->faker->uuid() . '.jpg' : null,
            'proof_file_size' => $method === 'upload' ? $this->faker->numberBetween(50000, 2000000) : null,
            'proof_file_mime' => $method === 'upload' ? 'image/jpeg' : null,
            'uploaded_at' => $method === 'upload' ? $this->faker->dateTimeBetween('-6 months', 'now') : null,
            'notes' => $this->faker->optional(0.4)->sentence(8),
            'rejection_reason' => null,
            'verified_by' => null,
            'verified_at' => null,
            'created_by' => null,
        ];
    }
}