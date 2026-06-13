<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentService
{
    /**
     * Create a payment record from an uploaded proof file for the authenticated user's active rental.
     *
     * $data must contain:
     * - `payment_month` (int|string) Month for the payment.
     * - `payment_year` (int|string) Year for the payment.
     * - `amount` (int|float) Payment amount.
     * - `notes` (string|null) Optional note.
     *
     * @param array $data Payment attributes and metadata (see description for expected keys).
     * @param \Illuminate\Http\UploadedFile $file Uploaded proof file (PDF expected).
     * @return \App\Models\Payment The created Payment model.
     * @throws \Exception Thrown with code 409 if a non-rejected payment for the same month/year already exists.
     * @throws \Exception Thrown with code 422 if an uploaded file declared as PDF does not have a valid PDF header.
     */
    public function upload(array $data, UploadedFile $file): Payment
    {
        $user = JWTAuth::parseToken()->authenticate();

        // Ambil rental aktif milik penyewa ini
        $rental = Rental::where('tenant_id', function ($query) use ($user) {
            $query->select('id')
                ->from('tenants')
                ->where('user_id', $user->id)
                ->limit(1);
        })->where('rental_status', 'aktif')->firstOrFail();

        // Cek duplikasi — satu bulan satu kali
        $exists = Payment::where('rental_id', $rental->id)
            ->where('payment_month', $data['payment_month'])
            ->where('payment_year', $data['payment_year'])
            ->whereNotIn('payment_status', ['ditolak'])
            ->exists();

        if ($exists) {
            throw new \Exception('Pembayaran bulan ini sudah pernah diupload', 409);
        }

        if ($file->getMimeType() === 'application/pdf') {
            $handle = fopen($file->getRealPath(), 'rb');
            $header = fread($handle, 5);
            fclose($handle);

            if ($header !== '%PDF-') {
                throw new \Exception('File PDF tidak valid', 422);
            }
        }

        // Simpan file
        $fileName  = time() . '_' . $file->getClientOriginalName();
        $filePath  = $file->storeAs('payment_proofs', $fileName, 'public');

        // Generate payment code
        $code = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        $payment = Payment::create([
            'payment_code'    => $code,
            'rental_id'       => $rental->id,
            'payment_month'   => $data['payment_month'],
            'payment_year'    => $data['payment_year'],
            'amount'          => $data['amount'],
            'payment_date'    => now(),
            'payment_method'  => 'upload',
            'payment_status'  => 'menunggu_verifikasi',
            'proof_file_name' => $file->getClientOriginalName(),
            'proof_file_path' => $filePath,
            'proof_file_size' => $file->getSize(),
            'proof_file_mime' => $file->getMimeType(),
            'uploaded_at'     => now(),
            'notes'           => $data['notes'] ?? null,
            'created_by'      => $user->id,
        ]);

        return $payment;
    }

    /**
     * Record a manual (offline) payment for a rental and mark it as verified.
     *
     * Creates a `Payment` with `payment_method = 'manual'`, `payment_status = 'terverifikasi'`,
     * sets verification metadata (`verified_by`, `verified_at`) to the authenticated user,
     * and returns the created `Payment`.
     *
     * @param array $data {
     *     Data required to create the manual payment.
     *
     *     @type int    $rental_id     ID of the rental to which the payment applies.
     *     @type int    $payment_month Month number of the payment.
     *     @type int    $payment_year  Year of the payment.
     *     @type float  $amount        Payment amount.
     *     @type string $payment_date  Date of payment (Y-m-d or other accepted format).
     *     @type string|null $notes    Optional notes for the payment.
     * }
     * @return \App\Models\Payment The newly created Payment model.
     * @throws \Exception If a non-rejected payment for the same rental/month/year already exists (HTTP 409).
     */
    public function manual(array $data): Payment
    {
        $user = JWTAuth::parseToken()->authenticate();

        $rental = Rental::findOrFail($data['rental_id']);

        // Cek duplikasi — satu bulan satu kali (kecuali yang sudah ditolak)
        $exists = Payment::where('rental_id', $rental->id)
            ->where('payment_month', $data['payment_month'])
            ->where('payment_year', $data['payment_year'])
            ->whereNotIn('payment_status', ['ditolak'])
            ->exists();

        if ($exists) {
            throw new \Exception('Pembayaran untuk bulan dan tahun ini sudah tercatat', 409);
        }

        // Generate payment code
        $code = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        $payment = Payment::create([
            'payment_code'   => $code,
            'rental_id'      => $rental->id,
            'payment_month'  => $data['payment_month'],
            'payment_year'   => $data['payment_year'],
            'amount'         => $data['amount'],
            'payment_date'   => $data['payment_date'],
            'payment_method' => 'manual',
            'payment_status' => 'terverifikasi',
            'notes'          => $data['notes'] ?? null,
            'verified_by'    => $user->id,
            'verified_at'    => now(),
            'created_by'     => $user->id,
        ]);

        return $payment;
    }

    /**
     * Retrieve payment history for the authenticated user's active rental.
     *
     * Returns payments ordered by `payment_year` then `payment_month`, both descending.
     *
     * @return array An array of payment records for the active rental; an empty array if no active rental is found.
     */
    public function history(): array
    {
        $user = JWTAuth::parseToken()->authenticate();

        $rental = Rental::where('tenant_id', function ($query) use ($user) {
            $query->select('id')
                ->from('tenants')
                ->where('user_id', $user->id)
                ->limit(1);
        })->where('rental_status', 'aktif')->first();

        if (!$rental) {
            return [];
        }

        return Payment::where('rental_id', $rental->id)
            ->orderByDesc('payment_year')
            ->orderByDesc('payment_month')
            ->get()
            ->toArray();
    }

    public function pending(): array
    {
        return Payment::where('payment_status', 'menunggu_verifikasi')
            ->with(['rental.tenant', 'rental.room.building'])
            ->orderBy('uploaded_at')
            ->get()
            ->toArray();
    }

    public function verify(int $id): Payment
    {
        $user    = JWTAuth::parseToken()->authenticate();
        $payment = Payment::findOrFail($id);

        $payment->update([
            'payment_status' => 'terverifikasi',
            'verified_by'    => $user->id,
            'verified_at'    => now(),
        ]);

        return $payment->fresh();
    }

    public function reject(int $id, string $reason): Payment
    {
        $user    = JWTAuth::parseToken()->authenticate();
        $payment = Payment::findOrFail($id);

        $payment->update([
            'payment_status'   => 'ditolak',
            'rejection_reason' => $reason,
            'verified_by'      => $user->id,
            'verified_at'      => now(),
        ]);

        return $payment->fresh();
    }
}