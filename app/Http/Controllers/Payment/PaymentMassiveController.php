<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentMassiveController extends Controller
{
     /**
     * Store a Massive Data Load of Payments
     */
    public function store(Request $request, PaymentService $paymentService)
    {
        return $paymentService->massiveStore($request);
    }
}
