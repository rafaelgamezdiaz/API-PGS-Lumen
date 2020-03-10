<?php


namespace App\Services;


use App\Models\Bill;
use App\Models\Payment;
use App\Traits\ApiResponser;
use function foo\func;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\ProvidesConvenienceMethods;
use PDOException;

class BillService extends BaseService
{
    use ApiResponser, ProvidesConvenienceMethods;

    public function index($request)
    {
        if (isset($_GET['where'])) {
            $bills = Bill::doWhere($request)
                ->where('account', $this->account)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        else{
            $bills =  Bill::where('account', $this->account)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        $bills->each(function($bills)
        {
            $bills->payment_info = collect($bills->payment)->only('username', 'amount_pending');
        });

        return $this->successResponse("Lista de asignaciones de pagos.", $bills);
    }

    /**
     * Create a new Bills-Payments conciliation
     */
    public function store($request, $bill)
    {
        $this->validate($request, $bill->rules());

        // Get the payment amount ids
        $payments_ids = collect($request->payment_id);

        // Get amounts available in payments
        $payments = $this->getAmountsAvailable($payments_ids);

        // Get Bills Amount Pending
        $bills_ids = collect($request->bill_id);
        $amounts = collect($request->amount);  // Bills amount (from API-ventas)
        $bills = $this->getBillsAmountPending($bills_ids, $amounts);

        $response = array();
        if ($this->testConnection($request)) {
            foreach ($bills as $bill)
            {
                if ($this->checkIFBillExist($request, $bill[0]['id'])) {
                    foreach ($payments as $payment)
                    {
                        $amount_available = Payment::findOrFail($payment['id'])->only(['amount_pending'])['amount_pending'];
                        if ($amount_available > 0) {
                            $quantity = $amount_available - $bill[1]['amount'];
                            if ( $quantity  <= 0) {
                                $amount_pending = abs($quantity);
                                $amount_paid = $amount_available;
                                $amount_available = 0;
                                $response[] = $this->cociliatePayment($request, $payment['id'], $bill[0]['id'], $amount_available, $amount_paid, $amount_pending);
                                $bill[1]['amount'] = $amount_pending;
                            }else if ( $quantity  > 0){ // Factura pagada completamente
                                if ($bill[1]['amount'] > 0) {
                                    $amount_pending = 0;
                                    $amount_paid = $bill[1]['amount'];
                                    $amount_available = abs($quantity);
                                    $response[] = $this->cociliatePayment($request, $payment['id'], $bill[0]['id'], $amount_available, $amount_paid, $amount_pending);
                                    $bill[1]['amount'] = $amount_pending;
                                }
                            }
                        }
                    }
                }
                else {
                    $response[] = ["message" => 'Error: Id = '.$bill[0]['id'].' No Registrado.'];
                }
            }
            return ($response !== false) ? $this->successResponse('Asignación del pago realizada con éxito!', $response) : $response;
        }
        return response()->json([
            "status" => 500,
            "message" => "No hay coneccion con API Ventas"
        ], 500);
    }

    /**
     * Returns all Payments for an specific Bill
     */
    public function showPayments($request, $id, $bill, $clientService, $methodService, $typeService)
    {
        $bills = Bill::where('bill_id', $id)
                     ->get();
        $bill_payments_list = collect();
        foreach ($bills as $bill)
        {
            // Get Payment Conciliated
            $bill_payment = $bill->payment;

            // Formating the Json response
            $temp = collect([
                'payment_id' => $bill->payment_id,
                'username_conciliation' => $bill->username,
                'date_conciliation' => $bill->created_at,
                'amount_paid' => $bill->amount_paid
            ])->merge([
                'type_id' => $typeService->show($bill_payment->type_id),
                'method_id' => $methodService->show($bill_payment->method_id),
                'client' => $clientService->getClient($request, $bill_payment->client_id, false),
                'username_payment' => $bill_payment->username,
                'payment_amount' => $bill_payment->amount,
                'payment_created_at' => $bill_payment->created_at
            ]);
            $bill_payments_list->push($temp);
        }
        if ( count($bill_payments_list) > 0 )
        {
            return $this->successResponse("Pagos conciliados con la factura: ".$id, $bill_payments_list);
        }
        return $this->successResponse("No se han conciliado pagos con la factura: ".$id);
    }

    /**
     * Return Bills pending amount to pay
     */
    public function getBillsAmountPending($bills_ids, $amounts)
    {
        $bills = collect();
        $bills_ids->each(function($bills_ids, $key) use ($bills, $amounts){
            $bills->push([
                    collect(['id'])->combine($bills_ids),
                    collect(['amount'])->combine($amounts[$key])]);
        });

        return $bills;
    }

    /**
     * Return payment available corresponding to ids selected by the user
     */
    public function getAmountsAvailable($payments_ids)
    {
        $payments = collect();
        $payments_ids->each(function ($payments_ids) use($payments){
            $payments->push(
                collect(
                    ['id' => $payments_ids,
                     'amount' => Payment::findOrFail($payments_ids)->only(['amount_pending'])['amount_pending']]));
        });
        return $payments->sortBy('amount')->where('amount','>',0);
    }

    /**
     * Mannage the conciliation to bills, also update the amount available fot the payment
     */
    public function doConciliation($request, $quantity, $amount_pending, $payment_id, $bill)
    {
        $this->cociliatePayment($request, $payment_id, $bill[0]['id'], $amount_paid, $amount_pending, $quantity);
        return $quantity >= 0 ? 0 : abs($quantity);
    }

    private function nonNullQuantities($quantity)
    {
        return $quantity > 0 ? $quantity : 0;
    }

    /**
     * Do the conciliation in bills table
     */
    public function cociliatePayment($request, $payment_id, $bill_id, $amount_available, $amount_paid, $amount_pending)
    {

        try {
            DB::beginTransaction();

            // Create Conciliations
            $bill = new Bill();
            $bill->bill_id = $bill_id;
            $bill->payment_id = $payment_id;
            $bill->username = $request->username;
            $bill->account = $request->account;
            $bill->amount_paid = $amount_paid;
            $bill->save();
            // Update payments amount available
            if ($bill->save()) {
                $payment = Payment::findOrFail($payment_id);
                $payment->amount_pending = $amount_available;
                if ($amount_available == 0) {
                    $payment->status = Payment::PAYMENT_STATUS_ASSIGNED;
                }
                $payment->update();
            }

            // POST to Sales API
            $url = env('SALES_SERVICE_BASE_URL') .env('SALES_SERVICE_PREFIX').'/amount/operation/' . $bill_id;
            $postSales = $this->doRequest($request,'PUT',  $url, ['amount' => $amount_pending]);
            LOG::info($postSales);
            if ($postSales['status'] == false) {
                DB::rollback();
                if (isset($postSales['connection']) == 'refused'){
                    return response()->json([
                        "status" => 500,
                        "message" => "No hay coneccion con API Ventas"
                    ], 500);
                }
            }
            elseif ($postSales['status'] == 200) {
                DB::commit();
                return ["message" => 'Factura Id = '.$bill_id.' conciliada'];
            }
        }
        catch (\Exception $e){
            DB::rollback();
            return response()->json([
                "status" => 500,
                "message" => "No se ha podido registrar el pago de la factura:"
            ], 500);
        }
    }


    public function updatePayment($id, $amount)
    {
        $payment = Payment::findOrFail($id);
        $payment->amount_pending = $amount;
        if ($amount == 0) {
            $payment->status = Payment::PAYMENT_STATUS_ASSIGNED;
        }
        return ($payment->update()) ? true : null;
    }

    private function testConnection($request)
    {
        $url = env('SALES_SERVICE_BASE_URL') .env('SALES_SERVICE_PREFIX');
        $connection_APIVentas = $this->doRequest($request,'GET',  $url);
        if (isset($connection_APIVentas['connection']) == 'refused'){
            return false;
        }
        return true;
    }

    private function checkIFBillExist($request, $bill_id)
    {
        $url = env('SALES_SERVICE_BASE_URL') .env('SALES_SERVICE_PREFIX').'/amount/operation/' . $bill_id;
        $billExist = $this->doRequest($request,'GET',  $url);
        if (isset($billExist['response']->status) ) {
            if ( $billExist['response']->status == 404 ) {
                return false;
            }
        }
        return true;
    }

    public function getBillNumber($request, $bill_id)
    {
        $url = env('SALES_SERVICE_BASE_URL') .env('SALES_SERVICE_PREFIX').'/operations/' . $bill_id;
        $billExist = $this->doRequest($request,'GET',  $url);
        if (isset($billExist['response']->status) ) {
            if ( $billExist['response']->status == 404 ) {
                return false;
            }
        }
        return $billExist;
    }

}
