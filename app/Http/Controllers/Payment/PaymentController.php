<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\ClientService;
use App\Services\PaymentService;
use App\Services\UserService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
     /**
     * Show Payments List
     */
    public function index(Request $request, PaymentService $paymentService, ClientService $clientService, UserService $userService)
    {
        return $paymentService->index($request, $clientService, $userService);
    }

     /**
     * Store a Payment
     */
    public function store(Request $request, Payment $payment, PaymentService $paymentService)
    {
        return $paymentService->store($request, $payment);
    }

     /**
     * Show an specific Payment
     */
    public function show(Request $request, $id, PaymentService $paymentService, ClientService $clientService, UserService $userService)
    {
        return $paymentService->show($request, $id, $clientService, $userService);
    }

     /**
     * Update a Payment
     */
    public function update(Request $request, $id, PaymentService $paymentService)
    {
        return $paymentService->update($request, $id);
    }

     /**
     * Remove a Payment
     */
    public function destroy($id, PaymentService $paymentService)
    {
        return $paymentService->destroy($id);
    }

}
