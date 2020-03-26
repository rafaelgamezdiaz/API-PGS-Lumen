<?php


namespace App\Services;


use App\Models\Bill;
use App\Models\Payment;
use App\Traits\ApiResponser;
use Carbon\Carbon;
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

    public function dataForReport($request, $clientService, $userService)
    {
        $payments = $this->getPayments($request);

        // Lot size. Makes queries to Customers-API and Users-API in lots of specific sizes
        // This is important because where is send by URL and can't be so larges
        $lot = 150;
        $clients_ids = $this->chunkIt($payments, 'client_id', $lot);
        $usernames = $this->chunkIt($payments, 'username', $lot);

        // Get Users and Clients
        $clients = $clientService->getClientsList($request, $clients_ids);
        $users = $userService->getUsersList($request, $usernames);
        if ($clients['status'] == 500 || $users['status'] == 500) {
            return [
                'status' => 500,
                'list' => $payments
            ];
        }
        $clients = $clients['list'];
        $users = $users['list'];

        // Get Payments with Relationships
        $payments = $payments->get();
        $payments->each(function($payments) use ($request, $clientService, $userService, $clients, $users)
            {
                $payments->type;
                $payments->method;
                $client_key = strval($payments->client_id);
                $user_key = strval($payments->username);
                $payments->client = array_key_exists($client_key, $clients) ? $clients[$client_key] : [];
                $payments->user = array_key_exists($user_key, $users) ? $users[$user_key] : [];
            });
        return [
            'status' => 200,
            'list' => $payments
        ];
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
        $payment->payment_date = Carbon::now();

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
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($request, $id)
    {
        $payment = Payment::findOrFail($id);
        if(count($payment->bills) > 0) {
            return $this->errorMessage('Lo sentimos, este pago ya se encuentra asignado a alguna factura, por lo que no puede ser eliminado.');
        }
        return $payment->changeStatus($request);
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
                     'amount_pending' => $payment_element['amount'],
                     'payment_date' => $payment_element['fecha_pago']
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

    private function chunkIt($source, $field, $lot){
        $item = $source->pluck($field)->unique()->toArray();
        $response = array();
        foreach ($item as $element){
            $response[] = $element;
        }
        return array_chunk($response, $lot);
    }
}
