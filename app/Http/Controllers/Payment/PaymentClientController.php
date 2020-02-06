<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\ClientService;
use App\Services\PaymentService;
use App\Services\UserService;
use Illuminate\Http\Request;

class PaymentClientController extends Controller
{
    /**
     * Show Payments List
     */
    public function index(Request $request, $id, PaymentService $paymentService, ClientService $clientService, UserService $userService)
    {
        return $paymentService->clientPayments($request, $id, $clientService, $userService);
    }
}
