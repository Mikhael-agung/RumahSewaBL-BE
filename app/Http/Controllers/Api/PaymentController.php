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
        //
    }

    public function store(StorePaymentRequest $request)
    {
        //
    }

    public function show(Payment $payment)
    {
        //
    }

    public function verify(VerifyPaymentRequest $request)
    {
        //
    }
}
