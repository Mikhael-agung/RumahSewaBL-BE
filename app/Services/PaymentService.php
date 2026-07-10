<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Rental;
use App\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Konversi angka bulan (1-12) jadi nama bulan Bahasa Indonesia.
     *
     * @param int|string $month
     * @return string
     */
    private function monthName($month): string
    {
        $names = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $names[(int) $month] ?? (string) $month;
    }

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

        $tenant = \App\Models\Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            throw new \Exception('Anda tidak memiliki data penyewa yang aktif. Silakan hubungi admin/manager.', 403);
        }

        $rental = Rental::where('tenant_id', $tenant->id)
            ->where('rental_status', 'active')
            ->first();

        if (!$rental) {
            throw new \Exception('Anda tidak memiliki rental aktif saat ini. Silakan hubungi admin/manager.', 403);
        }

        $rentalStartMonth = $rental->start_date->copy()->startOfMonth();
        $requestedMonth = \Carbon\Carbon::createFromDate(
            (int) $data['payment_year'],
            (int) $data['payment_month'],
            1
        )->startOfMonth();
        $currentMonth = now()->startOfMonth();

        if ($requestedMonth->lt($rentalStartMonth) || $requestedMonth->gt($currentMonth)) {
            throw new \Exception(
                'Bulan pembayaran harus antara ' . $this->monthName($rentalStartMonth->month) . ' ' . $rentalStartMonth->year
                . ' sampai ' . $this->monthName($currentMonth->month) . ' ' . $currentMonth->year
                . ' (sesuai masa sewa aktif Anda)',
                422
            );
        }

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

        $fileName  = time() . '_' . $file->getClientOriginalName();
        $filePath  = $file->storeAs('payment_proofs', $fileName, 'public');

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
            'Bukti pembayaran baru',
            "Penyewa mengupload bukti pembayaran untuk periode {$this->monthName($data['payment_month'])} {$data['payment_year']} (ID pembayaran #{$payment->id}), menunggu verifikasi."
        );

        return $payment;
    }

    /**
     * Update/timpa bukti pembayaran yang penyewa sendiri upload.
     * Hanya bisa dilakukan kalau payment_status masih 'menunggu_verifikasi'
     * (belum sempat diverifikasi/ditolak manager). File proof_file opsional —
     * kalau tidak dikirim, file lama tetap dipakai, cuma data lain yang diupdate.
     *
     * @throws \Exception 404 kalau payment tidak ditemukan / bukan milik penyewa ini.
     * @throws \Exception 422 kalau status payment sudah bukan 'menunggu_verifikasi'.
     * @throws \Exception 422 kalau file PDF yang diupload tidak valid.
     */
    public function update(int $id, array $data, ?UploadedFile $file = null): Payment
    {
        $user = JWTAuth::parseToken()->authenticate();

        $payment = Payment::whereHas('rental.tenant', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($id);

        if (!$payment) {
            throw new \Exception('Pembayaran tidak ditemukan', 404);
        }

        if ($payment->payment_status !== 'menunggu_verifikasi') {
            throw new \Exception('Pembayaran yang sudah diverifikasi/ditolak tidak bisa diubah', 422);
        }

        $updateData = [
            'payment_month' => $data['payment_month'],
            'payment_year'  => $data['payment_year'],
            'amount'        => $data['amount'],
            'notes'         => $data['notes'] ?? $payment->notes,
        ];

        if ($file) {
            if ($file->getMimeType() === 'application/pdf') {
                $handle = fopen($file->getRealPath(), 'rb');
                $header = fread($handle, 5);
                fclose($handle);

                if ($header !== '%PDF-') {
                    throw new \Exception('File PDF tidak valid', 422);
                }
            }

            if ($payment->proof_file_path && Storage::disk('public')->exists($payment->proof_file_path)) {
                Storage::disk('public')->delete($payment->proof_file_path);
            }

            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('payment_proofs', $fileName, 'public');

            $updateData['proof_file_name'] = $file->getClientOriginalName();
            $updateData['proof_file_path'] = $filePath;
            $updateData['proof_file_size'] = $file->getSize();
            $updateData['proof_file_mime'] = $file->getMimeType();
            $updateData['uploaded_at']     = now();
        }

        $payment->update($updateData);

        return $payment->fresh();
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

        $exists = Payment::where('rental_id', $rental->id)
            ->where('payment_month', $data['payment_month'])
            ->where('payment_year', $data['payment_year'])
            ->whereNotIn('payment_status', ['ditolak'])
            ->exists();

        if ($exists) {
            throw new \Exception('Pembayaran untuk bulan dan tahun ini sudah tercatat', 409);
        }

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

    /**
     * Retrieve payments for the manager/admin verification list.
     *
     * @param string|null $status Filter by exact payment_status ('menunggu_verifikasi', 'terverifikasi', 'ditolak').
     *                            Pass null or 'all' to return every status.
     * @return array
     */
    public function pending(?string $status = 'menunggu_verifikasi'): array
    {
        $query = Payment::with(['rental.tenant', 'rental.room.building'])
            ->orderBy('uploaded_at', 'desc');

        if ($status && $status !== 'all') {
            $query->where('payment_status', $status);
        }

        return $query->get()->toArray();
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
                'Pembayaran terverifikasi',
                "Pembayaran periode {$this->monthName($payment->payment_month)} {$payment->payment_year} (ID #{$payment->id}) sudah diverifikasi."
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
                'Pembayaran ditolak',
                "Pembayaran periode {$this->monthName($payment->payment_month)} {$payment->payment_year} (ID #{$payment->id}) ditolak. Alasan: {$reason}"
            );
        }

        return $payment->fresh();
    }

    /**
     * Update payment status ke 'terverifikasi' atau 'ditolak' lewat satu method.
     * Menggantikan pemanggilan terpisah verify()/reject() dari sisi controller.
     *
     * @param int $id
     * @param string $status 'terverifikasi' atau 'ditolak'
     * @param string|null $reason Wajib diisi kalau $status = 'ditolak'
     * @return Payment
     */
    public function updateStatus(int $id, string $status, ?string $reason = null): Payment
    {
        if ($status === 'terverifikasi') {
            return $this->verify($id);
        }

        if ($status === 'ditolak') {
            if (!$reason) {
                throw new \Exception('Alasan penolakan wajib diisi', 422);
            }
            return $this->reject($id, $reason);
        }

        throw new \Exception('Status tidak valid, gunakan "terverifikasi" atau "ditolak"', 422);
    }

    /**
     * Generate laporan pembayaran dengan filter opsional.
     *
     * Semua filter bersifat opsional — kalau tidak dikirim, dianggap tidak difilter.
     *
     * @param array $filters {
     *     @type int|null    $month       Filter berdasarkan payment_month (1-12).
     *     @type int|null    $year        Filter berdasarkan payment_year.
     *     @type string|null $status      Filter payment_status ('menunggu_verifikasi'|'terverifikasi'|'ditolak'), null/'all' = semua status.
     *     @type int|null    $building_id Filter berdasarkan gedung (lewat rental.room.building).
     *     @type int|null    $room_id     Filter berdasarkan kamar (lewat rental.room).
     *     @type int|null    $tenant_id   Filter berdasarkan penyewa (lewat rental.tenant).
     *     @type string|null $date_from   Filter payment_date >= tanggal ini (format Y-m-d).
     *     @type string|null $date_to     Filter payment_date <= tanggal ini (format Y-m-d).
     * }
     * @return array{summary: array, payments: array}
     */
    /**
     * Bangun query pembayaran terfilter, dipakai bareng oleh report() (JSON) dan
     * exportPayments() (Excel) biar logic filter-nya gak dobel.
     */
    private function buildReportQuery(array $filters = [])
    {
        return Payment::with([
            'rental' => fn($q) => $q->withTrashed(),
            'rental.tenant' => fn($q) => $q->withTrashed(),
            'rental.room.building',
        ])
            ->when(!empty($filters['month']), fn($q) => $q->where('payment_month', $filters['month']))
            ->when(!empty($filters['year']), fn($q) => $q->where('payment_year', $filters['year']))
            ->when(!empty($filters['status']) && $filters['status'] !== 'all', fn($q) => $q->where('payment_status', $filters['status']))
            ->when(!empty($filters['date_from']), fn($q) => $q->whereDate('payment_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn($q) => $q->whereDate('payment_date', '<=', $filters['date_to']))
            ->when(!empty($filters['building_id']), fn($q) => $q->whereHas('rental', fn($r) => $r->withTrashed()->whereHas('room', fn($r2) => $r2->where('building_id', $filters['building_id']))))
            ->when(!empty($filters['room_id']), fn($q) => $q->whereHas('rental', fn($r) => $r->withTrashed()->where('room_id', $filters['room_id'])))
            ->when(!empty($filters['tenant_id']), fn($q) => $q->whereHas('rental', fn($r) => $r->withTrashed()->where('tenant_id', $filters['tenant_id'])));
    }

    public function report(array $filters = []): array
    {
        $payments = $this->buildReportQuery($filters)->orderByDesc('payment_date')->get();

        $summary = [
            'total_count'               => $payments->count(),
            'total_amount_all'          => (float) $payments->sum('amount'),
            'total_amount_verified'     => (float) $payments->where('payment_status', 'terverifikasi')->sum('amount'),
            'count_menunggu_verifikasi' => $payments->where('payment_status', 'menunggu_verifikasi')->count(),
            'count_terverifikasi'       => $payments->where('payment_status', 'terverifikasi')->count(),
            'count_ditolak'             => $payments->where('payment_status', 'ditolak')->count(),
        ];

        return [
            'summary'  => $summary,
            'payments' => $payments->values()->toArray(),
        ];
    }

    /**
     * Ambil koleksi Payment terfilter (dipakai buat export Excel), tanpa
     * bungkusan summary/toArray seperti report().
     */
    public function exportPayments(array $filters = [])
    {
        return $this->buildReportQuery($filters)->orderByDesc('payment_date')->get();
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
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
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