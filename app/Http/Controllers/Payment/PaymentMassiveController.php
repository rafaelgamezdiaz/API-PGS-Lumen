<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\CsvImportService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentMassiveController extends Controller
{
     /**
     * Show Payments List
     */
     /* public function index(Request $request, PaymentService $paymentService, ClientService $clientService, UserService $userService)
        {
            return $paymentService->index($request, $clientService, $userService);
        }*/

     /**
     * Store a Massive Data Load of Payments
     */
    public function store(Request $request, PaymentService $paymentService)
    {
        return $paymentService->massiveStore($request);
    }



}
