<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentDeadline;
use App\Models\Rental;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentDeadlineService
{
    /**
     * Ambil deadline untuk bulan & tahun tertentu, beserta status bayar
     * tiap rental aktif untuk bulan tersebut.
     */
    public function getByMonth(int $month, int $year): array
    {
        $deadline = PaymentDeadline::with('creator')
            ->where('payment_month', $month)
            ->where('payment_year', $year)
            ->first();

        $rentals = Rental::with(['tenant', 'room.building'])
            ->where('rental_status', 'aktif')
            ->get();

        $paidRentalIds = Payment::where('payment_month', $month)
            ->where('payment_year', $year)
            ->where('payment_status', 'terverifikasi')
            ->pluck('rental_id')
            ->toArray();

        $rentalStatuses = $rentals->map(function (Rental $rental) use ($paidRentalIds) {
            return [
                'rental_id'    => $rental->id,
                'rental_code'  => $rental->rental_code,
                'tenant'       => $rental->tenant,
                'room'         => $rental->room,
                'is_paid'      => in_array($rental->id, $paidRentalIds),
            ];
        });

        return [
            'deadline' => $deadline,
            'rentals'  => $rentalStatuses,
        ];
    }

    /**
     * Set deadline baru untuk bulan & tahun tertentu.
     * Throw exception (409) jika deadline untuk bulan & tahun tersebut sudah ada.
     */
    public function setDeadline(int $month, int $year, string $deadlineDate): PaymentDeadline
    {
        $user = JWTAuth::parseToken()->authenticate();

        $exists = PaymentDeadline::where('payment_month', $month)
            ->where('payment_year', $year)
            ->exists();

        if ($exists) {
            throw new \Exception('Deadline untuk bulan dan tahun ini sudah ada. Gunakan endpoint update.', 409);
        }

        return PaymentDeadline::create([
            'payment_month' => $month,
            'payment_year'  => $year,
            'deadline_date' => $deadlineDate,
            'created_by'    => $user->id,
        ]);
    }

    /**
     * Update deadline yang sudah ada untuk bulan & tahun tertentu.
     * Throw exception (404) jika belum ada.
     */
    public function updateDeadline(int $month, int $year, string $deadlineDate): PaymentDeadline
    {
        $deadline = PaymentDeadline::where('payment_month', $month)
            ->where('payment_year', $year)
            ->first();

        if (!$deadline) {
            throw new \Exception('Deadline untuk bulan dan tahun ini belum diset.', 404);
        }

        $deadline->update([
            'deadline_date' => $deadlineDate,
        ]);

        return $deadline->fresh();
    }

    /**
     * Ambil semua deadline yang sudah lewat (deadline_date < hari ini)
     * dan masih ada rental aktif yang belum bayar untuk bulan tersebut.
     */
    public function getOverdue(): array
    {
        $overdueDeadlines = PaymentDeadline::where('deadline_date', '<', now()->toDateString())
            ->orderBy('payment_year')
            ->orderBy('payment_month')
            ->get();

        $result = [];

        foreach ($overdueDeadlines as $deadline) {
            $data = $this->getByMonth($deadline->payment_month, $deadline->payment_year);

            $unpaidRentals = collect($data['rentals'])->filter(fn ($r) => !$r['is_paid'])->values();

            if ($unpaidRentals->isNotEmpty()) {
                $result[] = [
                    'deadline'       => $deadline,
                    'unpaid_rentals' => $unpaidRentals,
                ];
            }
        }

        return $result;
    }
}