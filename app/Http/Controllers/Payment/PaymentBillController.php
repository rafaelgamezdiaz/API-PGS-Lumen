<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\BillService;
use App\Services\ClientService;
use App\Services\PaymentService;
use App\Services\UserService;
use Illuminate\Http\Request;

class PaymentBillController extends Controller
{

     /**
     * Store a full Payment of a Bill
     */
    public function store(Request $request, Payment $payment, PaymentService $paymentService, BillService $billService)
    {
        return $paymentService->billFullPayment($request, $payment, $billService);
    }

}
