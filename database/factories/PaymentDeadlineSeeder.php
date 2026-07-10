<?php

namespace Database\Seeders;

use App\Models\PaymentDeadline;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PaymentDeadlineSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::whereIn('role_id', [1, 2])->inRandomOrder()->first();

        if (! $creator) {
            $this->command->warn('PaymentDeadlineSeeder: tidak ada user admin/manager. Skip.');
            return;
        }

        // -2 bulan s.d. +2 bulan dari sekarang (5 total)
        // Pakai updateOrCreate KHUSUS di sini karena unique key-nya global
        // (payment_month, payment_year) -- kalau tabrakan sama data lama akan error kalau pakai create().
        $created = 0;
        for ($offset = -2; $offset <= 2; $offset++) {
            $month = Carbon::now()->addMonths($offset);

            $deadline = PaymentDeadline::updateOrCreate(
                [
                    'payment_month' => $month->month,
                    'payment_year' => $month->year,
                ],
                [
                    'deadline_date' => $month->copy()->startOfMonth()->addDays(9), // tanggal 10
                    'created_by' => $creator->id,
                ]
            );

            if ($deadline->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("PaymentDeadlineSeeder: {$created} deadline baru dibuat (sisanya sudah ada, di-skip).");
    }
}
