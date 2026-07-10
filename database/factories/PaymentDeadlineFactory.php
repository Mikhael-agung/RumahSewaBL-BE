<?php

namespace Database\Factories;

use App\Models\PaymentDeadline;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentDeadlineFactory extends Factory
{
    protected $model = PaymentDeadline::class;

    public function definition(): array
    {
        return [
            // payment_month, payment_year, deadline_date di-override oleh seeder
            'payment_month' => null,
            'payment_year' => null,
            'deadline_date' => null,
            'created_by' => null,
        ];
    }
}
