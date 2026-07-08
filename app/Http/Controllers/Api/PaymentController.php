<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\StorePaymentManualRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Services\ActivityLogService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected ActivityLogService $activityLogService;

    public function __construct(PaymentService $paymentService, ActivityLogService $activityLogService)
    {
        $this->paymentService = $paymentService;
        $this->activityLogService = $activityLogService;
    }

    public function upload(StorePaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->upload(
                $request->validated(),
                $request->file('proof_file')
            );

            $this->activityLogService->log(
                Auth::id(),
                'upload_payment',
                'Mengupload bukti pembayaran untuk rental ID: ' . $payment->rental_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Bukti pembayaran berhasil diupload',
                'data'    => $payment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], is_int($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }

    public function manual(StorePaymentManualRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->manual($request->validated());

            $this->activityLogService->log(
                Auth::id(),
                'input_manual_payment',
                'Menginput pembayaran manual/offline untuk rental ID: ' . $payment->rental_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran manual berhasil dicatat',
                'data'    => $payment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], is_int($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }

    public function update(UpdatePaymentRequest $request, int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->update(
                $id,
                $request->validated(),
                $request->file('proof_file')
            );

            $this->activityLogService->log(
                Auth::id(),
                'update_payment',
                'Memperbarui bukti pembayaran ID: ' . $id
            );

            return response()->json([
                'success' => true,
                'message' => 'Bukti pembayaran berhasil diperbarui',
                'data'    => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], is_int($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }

    public function history(): JsonResponse
    {
        try {
            $payments = $this->paymentService->history();
            return response()->json([
                'success' => true,
                'data'    => $payments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentVerify(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:all,menunggu_verifikasi,terverifikasi,ditolak',
        ]);

        try {
            $payments = $this->paymentService->pending($request->query('status', 'menunggu_verifikasi'));
            return response()->json([
                'success' => true,
                'data'    => $payments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status'            => 'required|string|in:terverifikasi,ditolak',
            'rejection_reason'  => 'required_if:status,ditolak|string|max:500',
        ]);

        try {
            $payment = $this->paymentService->updateStatus(
                $id,
                $request->status,
                $request->rejection_reason
            );

            $isReject = $request->status === 'ditolak';

            $this->activityLogService->log(
                Auth::id(),
                $isReject ? 'reject_payment' : 'verify_payment',
                ($isReject ? 'Menolak' : 'Memverifikasi') . ' pembayaran ID: ' . $id
                    . ($isReject ? ' — alasan: ' . $request->rejection_reason : '')
            );

            return response()->json([
                'success' => true,
                'message' => $isReject ? 'Pembayaran berhasil ditolak' : 'Pembayaran berhasil diverifikasi',
                'data'    => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], is_int($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }

    /**
     * Laporan pembayaran dengan filter opsional.
     *
     * Query param yang didukung: month, year, status, building_id, room_id,
     * tenant_id, date_from, date_to — semuanya opsional.
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'month'       => 'nullable|integer|min:1|max:12',
            'year'        => 'nullable|integer|min:2000|max:2100',
            'status'      => 'nullable|string|in:all,menunggu_verifikasi,terverifikasi,ditolak',
            'building_id' => 'nullable|integer|exists:buildings,id',
            'room_id'     => 'nullable|integer|exists:rooms,id',
            'tenant_id'   => 'nullable|integer|exists:tenants,id',
            'date_from'   => 'nullable|date',
            'date_to'     => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $report = $this->paymentService->report($request->only([
                'month',
                'year',
                'status',
                'building_id',
                'room_id',
                'tenant_id',
                'date_from',
                'date_to',
            ]));

            return response()->json([
                'success' => true,
                'data'    => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], is_int($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }


    public function exportExcel(Request $request)
    {
        $request->validate([
            'month'       => 'nullable|integer|min:1|max:12',
            'year'        => 'nullable|integer|min:2000|max:2100',
            'status'      => 'nullable|string|in:all,menunggu_verifikasi,terverifikasi,ditolak',
            'building_id' => 'nullable|integer|exists:buildings,id',
            'room_id'     => 'nullable|integer|exists:rooms,id',
            'tenant_id'   => 'nullable|integer|exists:tenants,id',
            'date_from'   => 'nullable|date',
            'date_to'     => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $payment = $this->paymentService->exportPayments($request->only([
                'month',
                'year',
                'status',
                'building_id',
                'room_id',
                'tenant_id',
                'date_from',
                'date_to',
            ]));

            $this->activityLogService->log(
                Auth::id(),
                'export_payment_report',
                'Mengekspor laporan pembayaran ke Excel'
            );

            $filename = 'Laporan-Pembayaran-' . now()->format('Ymd-His') . '.xlsx';
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PaymentsExport($payment),
                $filename
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], is_int($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }

    public function download(int $id)
    {
        try {
            $file = $this->paymentService->download($id);

            $this->activityLogService->log(
                Auth::id(),
                'download_payment_proof',
                'Mengunduh bukti pembayaran ID: ' . $id
            );

            return response()->download($file['path'], $file['name'], ['Content-Type' => $file['mime']]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], is_int($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }

    public function invoice(int $id)
    {
        try {
            $data = $this->paymentService->generateInvoice($id);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.kwitansi', $data)
                ->setPaper('a4', 'portrait');

            $this->activityLogService->log(
                Auth::id(),
                'download_invoice',
                'Mengunduh kwitansi pembayaran ID: ' . $id
            );

            return $pdf->download($data['filename']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], is_int($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }
}
