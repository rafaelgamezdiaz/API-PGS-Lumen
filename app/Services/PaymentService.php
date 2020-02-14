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
    public function index($request, $clientService, $userService)
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
        $payments->each(function($payments) use ($request, $clientService, $userService)
        {
            $payments->type;
            $payments->method;
            $payments->client = $clientService->getClient($request, $payments->client_id, false);
            $payments->user = $userService->getUser($request, $payments->username, false);
        });

        return $this->successResponse("Lista de Pagos", $payments);
    }

    /**
     * Store a payment
     */
    public function store($request, $payment)
    {
        $this->validate($request, $payment->rules());
        $payment->fill($request->all());
        $payment->amount_pending = $request->amount;
        if ($payment->save()) {
            return $this->successResponse('Pago registrado con éxito.', $payment);
        };
        return $this->errorMessage('Ha ocurrido un error al intentar guardar el pago.');
    }

    /**
     * Show payment details
     */
    public function show($request, $id, $clientService, $userService){
        $payment = Payment::findOrFail($id)
                           ->where('status', Payment::PAYMENT_STATUS_AVAILABLE);
        $payment->type;
        $payment->method;
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
     * Full payment of a Bill
     * Created to storage a payment from API-Ventas
     * $request->amount Is the total amount to pay of the bill
     */
    public function billFullPayment($request, $payment, $billService)
    {
        $this->validate($request, $payment->rulesFullBillPayment());
        $payment->fill($request->all());
        $payment->amount_pending = 0;

        // Check if was previously conciliated
        if ( $this->checkBillConciliated($request->bill_id) ) {
            return $this->errorMessage('Esa factura ya ya sido conciliada previamente');
        }

        if ($payment->save()) {
            Bill::create([
                'bill_id'       => $request->bill_id,
                'payment_id'    => $payment->id,
                'username'     => $request->username,
                'account'       => $request->account,
                'amount_paid'   => $request->amount
            ]);
            if ( $billService->updatePayment($payment->id, 0) ) {
                return $this->successResponse('Pago total de la factura registrado con éxito.', $payment);
            }
        };
        return $this->errorMessage('Ha ocurrido un error al intentar realizar el pago de la factura.');
    }

    public function checkBillConciliated ( $bill_id )
    {
        return count(Bill::where('bill_id',$bill_id)->get());
    }

    /**
     * Service to store massive data load of payment
     */
    public function massiveStore($request, $payment, $paymentService)
    {
        $this->validate($request, $payment->rulesMassivePayment());
        $clients_ids = $request->client_id;

        // REALIZAR POST AL ENDPOINT DE VENTAS
        try {
            DB::beginTransaction();
            foreach ($clients_ids as $key => $client_id)
            {
                Payment::create([
                    'client_id' => $client_id,
                    'type_id'   => $request->type_id[$key],
                    'method_id' => $request->method_id[$key],
                    'username'  => $request->username,
                    'account'   => $request->account,
                    'amount'    => $request->amount[$key],
                    'amount_pending' => $request->amount[$key]
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
}
