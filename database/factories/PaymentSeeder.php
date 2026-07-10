<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        // Cuma rental yang belum punya payment sama sekali (aman dari data lama)
        $rentals = Rental::whereDoesntHave('payments')
            ->whereIn('rental_status', ['active', 'ended'])
            ->get();

        $verifier = User::whereIn('role_id', [1, 2])->inRandomOrder()->first();

        if ($rentals->isEmpty() || ! $verifier) {
            $this->command->warn('PaymentSeeder: tidak ada rental baru atau verifier. Skip.');
            return;
        }

        $totalPayments = 0;
        $pendingForced = 0;

        foreach ($rentals as $rentalIndex => $rental) {
            $room = $rental->room;
            $amount = $room->monthly_price ?? 1500000;

            $start = Carbon::parse($rental->start_date)->startOfMonth();
            $end = $rental->end_date ? Carbon::parse($rental->end_date) : now();

            // Batasi maksimal 6 bulan payment per rental biar volume masih wajar
            $months = [];
            $cursor = $start->copy();
            while ($cursor->lte($end) && count($months) < 6) {
                $months[] = $cursor->copy();
                $cursor->addMonth();
            }

            foreach ($months as $i => $month) {
                $isLastMonth = $i === count($months) - 1;

                // Paksa beberapa payment terakhir jadi 'menunggu_verifikasi'
                // biar ada data buat testing tab "Menunggu Verifikasi" besok
                if ($isLastMonth && $rental->rental_status === 'active' && $pendingForced < 6) {
                    $status = 'menunggu_verifikasi';
                    $pendingForced++;
                } else {
                    $status = collect(['terverifikasi', 'terverifikasi', 'terverifikasi', 'menunggu_verifikasi', 'ditolak'])
                        ->random();
                }

                $paymentDate = $month->copy()->addDays(random_int(1, 8));

                $data = [
                    'rental_id' => $rental->id,
                    'payment_month' => $month->month,
                    'payment_year' => $month->year,
                    'amount' => $amount,
                    'payment_date' => $paymentDate,
                    'payment_status' => $status,
                ];

                if ($status === 'terverifikasi') {
                    $data['verified_by'] = $verifier->id;
                    $data['verified_at'] = $paymentDate->copy()->addDay();
                } elseif ($status === 'ditolak') {
                    $data['verified_by'] = $verifier->id;
                    $data['verified_at'] = $paymentDate->copy()->addDay();
                    $data['rejection_reason'] = collect([
                        'Bukti transfer buram, mohon upload ulang.',
                        'Nominal transfer tidak sesuai tagihan.',
                        'Bukti pembayaran tidak valid / tidak terbaca.',
                    ])->random();
                }
                // menunggu_verifikasi -> verified_by/verified_at biarin null (default factory)

                Payment::factory()->create($data);
                $totalPayments++;
            }
        }

        $this->command->info("PaymentSeeder: {$totalPayments} payment baru dibuat ({$pendingForced} dipaksa menunggu_verifikasi).");
    }
}
