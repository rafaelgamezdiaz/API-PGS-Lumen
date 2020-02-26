<?php


namespace App\Services;


use App\Models\Bill;
use App\Models\Payment;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\ProvidesConvenienceMethods;

class PaymentService extends BaseService
{
    use ApiResponser, ProvidesConvenienceMethods;

    /**
     * Return payment list
     */
    public function index($request, $clientService)
    {
        if (isset($_GET['where'])) {
            $payments = Payment::doWhere($request)
                ->where('account', $this->account)
                ->where('status', Payment::PAYMENT_STATUS_AVAILABLE)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        else{
            $payments =  Payment::where('account', $this->account)
                ->where('status', Payment::PAYMENT_STATUS_AVAILABLE)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $clients = $clientService->getClients($request, $this->account, false);

        //return $clients;
        foreach ( $clients as $client)
        {
           /*foreach ($payments as $payment)
            {
                if ($client['id'] == $payment->client_id) {
                    $payment->client_commerce_name = $client['commerce_name'];
                    break 2;
                }
            }*/
           $response = array();
           foreach ($payments as $payment)
           {
               if ($client['id'] == $payment->client_id) {
                   array_push($response, ['client_commerce_name' => $client['commerce_name']]);
                  // $payment->push(['client_commerce_name' => $client['commerce_name']]);
                   //$payment->client_commerce_name = $client['commerce_name'];
                   break;
               }
           }
            /*$payments->each(function($payments) use($client){
                if ($client['id'] == $payments->client_id) {
                    $payments->client_commerce_name = $client['commerce_name'];
                }
            } );*/
            /*{
                if ($client['id'] == $payment->client_id) {
                    $payment->client_commerce_name = $client['commerce_name'];
                    break 2;
                }
            }*/
            /*$payments->each(function($payments) use ($request, $client)
            {
                $payments->type;
                $payments->method;

                //$payments->client = $clientService->getClients($request, $payments->client_id, false);
                //$payments->user = $userService->getUser($request, $payments->username, false);
            });*/
        }

        return $this->successResponse("Lista de Pagos", $response);

    }

    /**
     * Store a payment
     */
    public function store($request, $payment, $billService)
    {
        $payment->fill($request->all());
        $payment->amount_pending = $request->amount;

        if ($request->has('bill_id')) {   // If it is a Payment with Conciliation operations
            $this->validate($request, $payment->rulesPaymentConciliated());
            if ($payment->save()) {

                $payment->amount_pending = 0;
                // Conciliate the payment
                Bill::create([
                    'bill_id'       => $request->bill_id,
                    'payment_id'    => $payment->id,
                    'username'      => $request->username,
                    'account'       => $request->account,
                    'amount_paid'   => $request->amount
                ]);

                if ( $billService->updatePayment($payment->id, 0) ) {
                    return $this->successResponse('Pago registrado con éxito.', $payment);
                }
            }
        }
        else{    // If it is a Payment operation (without automatic conciliation)
            $this->validate($request, $payment->rules());
            if ($payment->save()) {
                return $this->successResponse('Pago registrado con éxito.', $payment);
            }
        };

        return $this->errorMessage('Ha ocurrido un error al intentar realizar el pago de la factura.');
    }

    /**
     * Show payment details
     */
    public function show($request, $id, $clientService, $userService){
        $payment = Payment::findOrFail($id)
                           ->where('status', Payment::PAYMENT_STATUS_AVAILABLE);
        $payment->type_id;
        $payment->method_id;
        $payment->client = $clientService->getClient($request, $payment->client_id, false);
        $payment->user = $userService->getUser($request, $payment->username, false);
        return $payment;
    }

    /**
     * Update payment info
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
            return $this->successResponse("Pago actualizado con éxito.");
        }
        return $this->errorMessage('Ha ocurrido un error al intentar actualizar el pago.', 409);
    }

    /**
     * Delete a payment ( only if it has not been conciliated with a bill )
     */
    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        if(count($payment->bills) > 0) {
            return $this->errorMessage('Lo sentimos, este pago ya se encuentra asignado a alguna factura, por lo que no puede ser eliminado.');
        }
        if ($payment->delete())
        {
            return $this->successResponse('La información del pago ha sido eliminada.');
        }
        return $this->errorMessage('Ha ocurrido un error al intentar eliminar el pago.');
    }

    /**
     * Return payments of an specific client
     */
    public function clientPayments($request, $id, $clientService, $userService)
    {
        if (isset($_GET['where'])) {
            $payments = Payment::doWhere($request)
                ->where('account', $this->account)
                ->where('status', Payment::PAYMENT_STATUS_AVAILABLE)
                ->where('client_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        else{
            $payments =  Payment::where('account', $this->account)
                ->where('client_id', $id)
                ->where('status', Payment::PAYMENT_STATUS_AVAILABLE)
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

    /**
     * Service to store massive data load of payment
     */
    public function massiveStore($request)
    {
        $payments = $request->payments;
        try {
            DB::beginTransaction();
            foreach ($payments as $payment_element)
            {
                 Payment::create([
                     'client_id' => $payment_element['client_id'],
                     'type_id'   => 2,  // 'A Recibir' default for massive payments
                     'method_id' => $payment_element['method_id'],
                     'username'  => $this->username,
                     'account'   => $this->account,
                     'amount'    => $payment_element['amount'],
                     'amount_pending' => $payment_element['amount']
                 ]);
            }
            DB::commit();
        }
        catch (\Exception $e){
            DB::rollback();
            return response()->json([
                "status" => 500,
                "message" => "No se ha podido realizar la carga masiva de pagos."
            ], 500);
        }
        return $this->successResponse('Carga masiva de pagos realizada con éxito.');
    }

    public function checkBillConciliated ( $bill_id )
    {
        return count(Bill::where('bill_id',$bill_id)->get());
    }

}
