<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentDeadline;
use App\Models\Rental;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentDeadlineService
{
    /**
     * Retrieve the payment deadline for a specific month and year and list active rentals with their payment status for that period.
     *
     * @return array{deadline: ?\App\Models\PaymentDeadline, rentals: \Illuminate\Support\Collection<int, array{rental_id: int, rental_code: string, tenant: \App\Models\Tenant|null, room: \App\Models\Room|null, is_paid: bool}>}
     *   An associative array with:
     *     - `deadline`: the PaymentDeadline model for the given month/year, or `null` if none exists.
     *     - `rentals`: a collection of rental status entries; each entry contains `rental_id`, `rental_code`, the related `tenant`, the related `room`, and `is_paid` which is `true` when a verified payment exists for that rental in the given month/year.
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
     * Create a new payment deadline for the specified month and year.
     *
     * @param int $month Month number (1-12).
     * @param int $year Four-digit year.
     * @param string $deadlineDate Deadline date string in `YYYY-MM-DD` format.
     * @return PaymentDeadline The newly created PaymentDeadline model.
     * @throws \Exception If a deadline for the given month and year already exists (HTTP-style code 409).
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
         * Update the payment deadline date for a specific month and year.
         *
         * Updates the existing PaymentDeadline's `deadline_date` and returns the refreshed model.
         *
         * @param int $month Month number (1-12) for which the deadline applies.
         * @param int $year Four-digit year for which the deadline applies.
         * @param string $deadlineDate Date string for the new deadline (expected format: `Y-m-d`).
         * @return PaymentDeadline The updated PaymentDeadline model.
         * @throws \Exception If no PaymentDeadline exists for the given month and year (exception code 404).
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
     * List payment deadlines before today that have active rentals unpaid for that month.
     *
     * For each overdue deadline returns an associative array entry containing the overdue PaymentDeadline model
     * and a collection of active rentals for that month which have not been verified as paid.
     *
     * @return array<int, array{deadline: \App\Models\PaymentDeadline, unpaid_rentals: \Illuminate\Support\Collection}> Array of entries, each with `deadline` and `unpaid_rentals`.
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