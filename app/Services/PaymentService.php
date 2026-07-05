<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentService
{
    // protected NotificationService $notificationService;

    // public function __construct(NotificationService $notificationService)
    // {
    //     $this->notificationService = $notificationService;
    // }

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
        })->where('rental_status', 'active')->firstOrFail();

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

        $this->notificationService->sendToRoles(
            ['manager', 'administrator'],
            'payment_uploaded',
            'Bukti pembayaran baru',
            "Penyewa mengupload bukti pembayaran untuk periode {$data['payment_month']}/{$data['payment_year']}, menunggu verifikasi.",
            ['payment_id' => $payment->id, 'rental_id' => $rental->id]
        );

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
        })->where('rental_status', 'active')->first();

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
        $payment = Payment::with('rental.tenant')->findOrFail($id);

        $payment->update([
            'payment_status' => 'terverifikasi',
            'verified_by'    => $user->id,
            'verified_at'    => now(),
        ]);

        if ($payment->rental && $payment->rental->tenant) {
            $this->notificationService->send(
                $payment->rental->tenant->user_id,
                'payment_verified',
                'Pembayaran terverifikasi',
                "Pembayaran periode {$payment->payment_month}/{$payment->payment_year} sudah diverifikasi.",
                ['payment_id' => $payment->id]
            );
        }

        return $payment->fresh();
    }

    public function reject(int $id, string $reason): Payment
    {
        $user    = JWTAuth::parseToken()->authenticate();
        $payment = Payment::with('rental.tenant')->findOrFail($id);

        $payment->update([
            'payment_status'   => 'ditolak',
            'rejection_reason' => $reason,
            'verified_by'      => $user->id,
            'verified_at'      => now(),
        ]);

        if ($payment->rental && $payment->rental->tenant) {
            $this->notificationService->send(
                $payment->rental->tenant->user_id,
                'payment_rejected',
                'Pembayaran ditolak',
                "Pembayaran periode {$payment->payment_month}/{$payment->payment_year} ditolak. Alasan: {$reason}",
                ['payment_id' => $payment->id]
            );
        }

        return $payment->fresh();
    }

    /**
     * Resolve the storage path and original file name for a payment's proof file,
     * after verifying the authenticated user is allowed to access it.
     *
     * A `penyewa` may only access proof files belonging to their own rental.
     * `manager` and `administrator` may access any payment's proof file.
     *
     * @param int $id Payment ID.
     * @return array{path: string, name: string, mime: string} Absolute disk path, original file name, and mime type.
     * @throws \Exception If the payment/proof does not exist (404) or the user is not authorized (403).
     */
    public function download(int $id): array
    {
        $user = JWTAuth::parseToken()->authenticate();

        $payment = Payment::with('rental.tenant')->findOrFail($id);

        if (!$payment->proof_file_path) {
            throw new \Exception('Bukti pembayaran tidak ditemukan untuk pembayaran ini', 404);
        }

        $userRole = DB::table('roles')->where('id', $user->role_id)->value('name');
        $isStaff  = in_array($userRole, ['manager', 'administrator']);
        $isOwner  = $payment->rental
            && $payment->rental->tenant
            && $payment->rental->tenant->user_id === $user->id;

        if (!$isStaff && !$isOwner) {
            throw new \Exception('Anda tidak berhak mengakses bukti pembayaran ini', 403);
        }

        if (!Storage::disk('public')->exists($payment->proof_file_path)) {
            throw new \Exception('File bukti pembayaran tidak ditemukan di server', 404);
        }

        return [
            'path' => Storage::disk('public')->path($payment->proof_file_path),
            'name' => $payment->proof_file_name ?? basename($payment->proof_file_path),
            'mime' => $payment->proof_file_mime ?? 'application/octet-stream',
        ];
    }

    /**
     * Resolve and authorize the data needed to render a payment invoice/kwitansi PDF.
     *
     * Only payments with `payment_status = 'terverifikasi'` have an invoice.
     * A `penyewa` may only access their own rental's invoice. `manager` and
     * `administrator` may access any invoice.
     *
     * @param int $id Payment ID.
     * @return array{payment: Payment, tenant: ?\App\Models\Tenant, room: ?\App\Models\Room, building: ?\App\Models\Building, periodLabel: string, paymentMethodLabel: string, verifiedByName: ?string, filename: string}
     * @throws \Exception If not found (404), not yet verified (422), or not authorized (403).
     */
    public function generateInvoice(int $id): array
    {
        $user = JWTAuth::parseToken()->authenticate();

        $payment = Payment::with(['rental.tenant', 'rental.room.building', 'verifiedBy'])->findOrFail($id);

        if ($payment->payment_status !== 'terverifikasi') {
            throw new \Exception('Kwitansi hanya tersedia untuk pembayaran yang sudah terverifikasi', 422);
        }

        $userRole = DB::table('roles')->where('id', $user->role_id)->value('name');
        $isStaff  = in_array($userRole, ['manager', 'administrator']);
        $isOwner  = $payment->rental
            && $payment->rental->tenant
            && $payment->rental->tenant->user_id === $user->id;

        if (!$isStaff && !$isOwner) {
            throw new \Exception('Anda tidak berhak mengakses kwitansi ini', 403);
        }

        $methodLabels = [
            'upload' => 'Transfer (Upload Bukti)',
            'manual' => 'Manual / Tunai',
        ];

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return [
            'payment'             => $payment,
            'tenant'              => $payment->rental->tenant ?? null,
            'room'                => $payment->rental->room ?? null,
            'building'            => $payment->rental->room->building ?? null,
            'periodLabel'         => ($monthNames[$payment->payment_month] ?? $payment->payment_month) . ' ' . $payment->payment_year,
            'paymentMethodLabel'  => $methodLabels[$payment->payment_method] ?? $payment->payment_method,
            'verifiedByName'      => $payment->verifiedBy->username ?? null,
            'filename'            => 'Kwitansi-' . $payment->payment_code . '.pdf',
        ];
    }
}