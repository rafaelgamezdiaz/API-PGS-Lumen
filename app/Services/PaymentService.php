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

        return $this->successResponse("Lista de Pagos", $payments);
    }

    public function store($request, $payment)
    {
        $this->validate($request, $payment->rules());
        $payment->fill($request->all());
        $payment->amount_pending = $request->amount;
        if ($payment->save()) {
            return $this->successResponse('Payment was saved!', $payment);
        };
        return $this->errorMessage('Ha ocurrido un error al intentar guardar el pago.');
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

        // Check if the Payment is already assigned.
        $bills = $payment->has('bills')->get();
        if(count($bills)) {
            return $this->errorMessage('Lo sentimos, este pago ya se encuentra asignado a alguna factura, por lo que no puede ser actualizado.');
        };

        $this->validate($request, $payment->rules_update());

        // Only the amount_pending needs to be updated
        $payment->fill($request->all());
        //$payment->amount_pending = $request->amount_pending;

        if ($payment->update()) {
            return $this->successResponse("Payment was updated!");
        }
        return $this->errorMessage('Ha ocurrido un error al intentar actualizar el pago!', 409);
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);

        // Check if the Payment is already assigned.
        $bills = $payment->has('bills')->get();
        if(count($bills)) {
            return $this->errorMessage('Lo sentimos, este pago ya se encuentra asignado a alguna factura, por lo que no puede ser eliminado.');
        };

        if ($payment->delete())
        {
            return $this->successResponse('La información del pago ha sido eliminada.');
        }
        return $this->errorMessage('Ha ocurrido un error al intentar eliminar el pago.');
    }

    public function clientPayments($request, $id, $clientService, $userService)
    {
        if (isset($_GET['where'])) {
            $payments = Payment::doWhere($request)
                ->where('account', $this->account)
                ->where('client_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        else{
            $payments =  Payment::where('account', $this->account)
                ->where('client_id', $id)
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

        return $this->successResponse("Lista de Pagos del Cliente", $payments);
    }
}
