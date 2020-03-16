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
    public function index($request, $clientService, $userService, $is_query_report = false)
    {
        $payments = $this->getPayments($request);
        $limit = $request->has('limit') ? $request->input('limit') : 10;
        $payments = ($request->has('paginate') && $request->paginate=='true') ? $payments->paginate($limit) : $payments->get();
        if ($is_query_report == true) {
            $payments->each(function($payments) use ($request, $clientService, $userService)
            {
                $payments->type;
                $payments->method;
                $payments->client = $clientService->getClient($request, $payments->client_id, false);
                $payments->user = $userService->getUser($request, $payments->username, false);
            });
            return $payments;
        }
        $payments->each(function($payments) use ($request, $clientService, $userService)
        {
            $payments->type;
            $payments->method;
            $payments->client = $clientService->getClient($request, $payments->client_id, false);
            $payments->user = $userService->getUser($request, $payments->username, false);
        });
        return $this->simpleResponse($payments);
    }

    private function getPayments($request)
    {
        if (isset($_GET['where'])) {
            return Payment::doWhere($request)
                ->where('account', $this->account)
                ->orderBy('status', 'desc')
                ->orderBy('created_at', 'desc');
        }
        else{
            return Payment::where('account', $this->account)
                ->orderBy('status', 'desc')
                ->orderBy('created_at', 'desc');
        }
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
    public function show($request, $id, $clientService, $userService, $billService){
        $payment = Payment::findOrFail($id);

        // Get Payment Bills
        $bills = $payment->bills;

        $bills->each(function($bills) use($request, $billService){
            $bills->invoice_id = $billService->getBillNumber($request, $bills->bill_id)['operation']['id_invoice'];
            $bills->total_amount = $billService->getBillNumber($request, $bills->bill_id)['operation']['total'];
        });

        unset($payment['bills']);
        $payment->bills = $bills;

        // Get Paymemnt type
        $type = $payment->type['type'];
        unset($payment['type']);
        $payment->type = $type;

        // Get Payment Method
        $method = $payment->method['method'];
        unset($payment['method']);
        $payment->method = $method;

        // Get Payment Client
        $payment->client = $clientService->getClient($request, $payment->client_id, false);

        // Get Payment User
        $payment->user = $userService->getUser($request, $payment->username, false);

        return $this->simpleResponse($payment);
    }

    /**
     * Update payment info
     */
    public function update($request, $id)
    {
        $payment = Payment::findOrFail($id);

        // Check if the Payment is already assigned.
        $bills = $payment->bills;
        if(count($bills)) {
            return $this->errorMessage('Lo sentimos, este pago ya se encuentra asignado a alguna factura, por lo que no puede ser actualizado.');
        };

        if( $this->checkCode($request->reference, $id) )
        {
            return $this->errorMessage('Lo sentimos, la refenrecia: '.$request->reference.', ya esta siendo utilizada en otro pago');
        };

        $this->validate($request, $payment->rules_update());

        // Only the amount_pending needs to be updated
        $payment->fill($request->all());
        $payment->amount_pending = $request->amount;

        if ($payment->update()) {
            return $this->successResponse("Pago actualizado con éxito.");
        }
        return $this->errorMessage('Ha ocurrido un error al intentar actualizar el pago.', 409);
    }

    public function checkCode($reference, $id)
    {
        $payments = Payment::whereNotIn('id',[$id])
                           ->where('reference', $reference)
                           ->where('account', $this->account)
                           ->get();
        return count($payments) > 0;
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
                     'reference' => $payment_element['document'],
                     'amount_pending' => $payment_element['amount']
                 ]);
            }
            DB::commit();
        }
        catch (\Exception $e){
            DB::rollback();
            return response()->json([
                "status" => 500,
                "message" => "No se ha podido realizar la carga masiva de pagos. Alguno de los pagos está siendo creado por duplicado."
            ], 500);
        }
        return $this->successResponse('Carga masiva de pagos realizada con éxito.');
    }

    public function checkBillConciliated ( $bill_id )
    {
        return count(Bill::where('bill_id',$bill_id)->get());
    }

}
