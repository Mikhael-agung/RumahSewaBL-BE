<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentService
{
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