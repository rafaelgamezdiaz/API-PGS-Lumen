<?php

namespace App\Http\Controllers\Bill;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Services\BillService;
use App\Services\ClientService;
use App\Services\MethodService;
use App\Services\TypeService;
use Illuminate\Http\Request;

class BillPaymentController extends Controller
{

    /**
     * Create a new Bills-Payments conciliation
     */
    public function show(Request $request, $id, Bill $bill, BillService $billService, ClientService $clientService, MethodService $methodService, TypeService $typeService)
    {
        return $billService->showPayments($request, $id, $bill, $clientService, $methodService, $typeService);
    }
}
