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

    /**
     * Create a new controller instance and set the payment deadline service.
     *
     * @param PaymentDeadlineService $paymentDeadlineService The service used to manage payment deadlines.
     */
    public function __construct(PaymentDeadlineService $paymentDeadlineService)
    {
        $this->paymentDeadlineService = $paymentDeadlineService;
    }

    /**
     * Return payment deadlines for the given month and year.
     *
     * Reads `month` and `year` from query parameters and defaults to the current month and year when absent.
     *
     * @param Request $request HTTP request containing optional `month` and `year` query parameters.
     * @return JsonResponse JSON with `success: true` and `data` containing the deadlines for the specified month and year.
     */
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

    /**
     * Create a payment deadline for the specified month and year.
     *
     * Accepts validated input and persists a deadline record.
     *
     * @param StorePaymentDeadlineRequest $request Request containing `payment_month`, `payment_year`, and `deadline_date`.
     * @return JsonResponse JSON with one of the following shapes:
     *                      - Success (HTTP 201): `{ "success": true, "message": "Deadline berhasil disimpan", "data": <deadline> }`
     *                      - Error (HTTP exception code or 500): `{ "success": false, "message": "<error message>" }`
     */
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

    /**
     * Update the payment deadline for the specified month and year.
     *
     * Updates the stored deadline date for the deadline identified by the given month and year using
     * the `deadline_date` provided in the request.
     *
     * @param UpdatePaymentDeadlineRequest $request Request containing `deadline_date`.
     * @param int $month Month number (1-12) identifying the deadline to update.
     * @param int $year Four-digit year identifying the deadline to update.
     * @return JsonResponse JSON object with:
     *                      - `success` (bool): `true` on success, `false` on error.
     *                      - `message` (string): Human-readable status message.
     *                      - `data` (mixed): The updated deadline resource when `success` is `true`.
     */
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

    /**
     * Retrieve overdue payment deadlines.
     *
     * @return JsonResponse JSON response with `success: true` and `data` containing the list of overdue deadlines.
     */
    public function overdue(): JsonResponse
    {
        $data = $this->paymentDeadlineService->getOverdue();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 200);
    }
}