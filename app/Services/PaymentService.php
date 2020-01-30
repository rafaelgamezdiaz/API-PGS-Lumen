<?php


namespace App\Services;


use App\Models\Payment;
use App\Traits\ApiResponser;
use Laravel\Lumen\Routing\ProvidesConvenienceMethods;

class PaymentService extends BaseService
{
    use ApiResponser, ProvidesConvenienceMethods;

    public function index($request, $clientService, $userService)
    {
        if (isset($_GET['where'])) {
            $payments = Payment::doWhere($request)
                ->where('account', $this->account)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        else{
            $payments =  Payment::where('account', $this->account)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        $payments->each(function($payments) use ($request, $clientService, $userService)
        {
            $payments->type;
            $payments->method;
            $payments->client = $clientService->getClient($request, $payments->client_id, false);
            $payments->user = $userService->getUser($request, $payments->username, false);
        });

        return $this->successResponse("List of Payments", $payments);
    }

    public function store($request, $payment)
    {
        $this->validate($request, $payment->rules());
        $payment->fill($request->all());
        if ($payment->save()) {
            return $this->successResponse('Payment was saved!', $payment);
        };
        return $this->errorMessage('Sorry! Something was wrong, payment was not saved. Please try againg.');
    }

    public function show($request, $id, $clientService, $userService){
        $payment = Payment::findOrFail($id);
        $payment->type;
        $payment->method;
        $payment->client = $clientService->getClient($request, $payment->client_id, false);
        $payment->user = $userService->getUser($request, $payment->username, false);

        return $payment;
    }

    /**
     * Update the Subscription
     */
    public function update($request, $id)
    {
        $payment = Payment::findOrFail($id);
        $this->validate($request, $payment->rules_update());
        $payment->amount_pending = $request->amount_pending;
        if ($payment->update()) {
            return $this->successResponse("Payment was updated!");
        }
        return $this->errorMessage('Sorry. Something happends when trying to update the payment!', 409);
    }
}
