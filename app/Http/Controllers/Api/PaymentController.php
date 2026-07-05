<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
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
            ], $e->getCode() ?: 500);
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

    public function pending(): JsonResponse
    {
        try {
            $payments = $this->paymentService->pending();
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

    public function verify(Request $request, int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->verify($id);

            $this->activityLogService->log(
                Auth::id(),
                'verify_payment',
                'Memverifikasi pembayaran ID: ' . $id
            );

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diverifikasi',
                'data'    => $payment,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $payment = $this->paymentService->reject($id, $request->rejection_reason);

            $this->activityLogService->log(
                Auth::id(),
                'reject_payment',
                'Menolak pembayaran ID: ' . $id . ' — alasan: ' . $request->rejection_reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil ditolak',
                'data'    => $payment,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function download(int $id) {
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
            ], $e->getCode() ?: 500);
        }
    }
}