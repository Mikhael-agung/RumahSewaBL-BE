<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Services\PaymentService;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        // memang belum ada isinya
    }

    public function store(StorePaymentRequest $request)
    {
        // memang belum ada isinya
    }

    public function show(Payment $payment)
    {
        // memang belum ada isinya
    }

    public function verify(VerifyPaymentRequest $request)
    {
        // memang belum ada isinya
    }
}
