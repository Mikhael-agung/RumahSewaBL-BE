<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentDeadlineRequest;
use App\Http\Requests\UpdatePaymentDeadlineRequest;
use App\Services\PaymentDeadlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentDeadlineController extends Controller
{
    protected PaymentDeadlineService $paymentDeadlineService;

    public function __construct(PaymentDeadlineService $paymentDeadlineService)
    {
        $this->paymentDeadlineService = $paymentDeadlineService;
    }

    public function index(Request $request): JsonResponse
    {
        $month = (int) ($request->query('month') ?? now()->month);
        $year  = (int) ($request->query('year') ?? now()->year);

        $data = $this->paymentDeadlineService->getByMonth($month, $year);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 200);
    }

    public function store(StorePaymentDeadlineRequest $request): JsonResponse
    {
        try {
            $deadline = $this->paymentDeadlineService->setDeadline(
                $request->payment_month,
                $request->payment_year,
                $request->deadline_date
            );

            return response()->json([
                'success' => true,
                'message' => 'Deadline berhasil disimpan',
                'data'    => $deadline,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function update(UpdatePaymentDeadlineRequest $request, int $month, int $year): JsonResponse
    {
        try {
            $deadline = $this->paymentDeadlineService->updateDeadline(
                $month,
                $year,
                $request->deadline_date
            );

            return response()->json([
                'success' => true,
                'message' => 'Deadline berhasil diperbarui',
                'data'    => $deadline,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function overdue(): JsonResponse
    {
        $data = $this->paymentDeadlineService->getOverdue();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 200);
    }
}