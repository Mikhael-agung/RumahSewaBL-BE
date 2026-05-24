<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    // POST /api/payments/upload (penyewa)
    public function upload(StorePaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->upload(
                $request->validated(),
                $request->file('proof_file')
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

    // GET /api/payments/history (penyewa)
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

    // GET /api/payments/pending (manager/administrator)
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

    // POST /api/payments/{id}/verify (manager/administrator)
    public function verify(Request $request, int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->verify($id);

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

    // POST /api/payments/{id}/reject (manager/administrator)
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $payment = $this->paymentService->reject($id, $request->rejection_reason);

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
}