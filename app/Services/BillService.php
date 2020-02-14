<?php


namespace App\Services;


use App\Models\Bill;
use App\Models\Payment;
use App\Traits\ApiResponser;
use function foo\func;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        foreach ($bills as $bill)
        {
            foreach ($payments as $payment)
            {
                $amount_available = Payment::findOrFail($payment['id'])->only(['amount_pending'])['amount_pending'];
                if ($amount_available > 0) {
                    $quantity = $amount_available - $bill[1]['amount'];
                    if ( $quantity  <= 0) {
                        $bill[1]['amount'] = $this->doConciliation($request, $quantity, $amount_available, $payment['id'], $bill);
                    }else if ( $quantity  > 0){
                        if ($bill[1]['amount'] > 0) {
                            $bill[1]['amount'] = $this->doConciliation($request, $quantity, $bill[1]['amount'], $payment['id'], $bill);
                        }
                    }
                }
            }
        }
        return $this->successResponse('Asignación del pago realizada con éxito!');
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
        $amount_paid = $amount_pending;
        $this->cociliatePayment($request, $payment_id, $bill[0]['id'], $amount_paid);
        $this->updatePayment($payment_id, $this->nonNullQuantities($quantity));
        return $quantity >= 0 ? 0 : abs($quantity);
    }

    private function nonNullQuantities($quantity)
    {
        return $quantity > 0 ? $quantity : 0;
    }

    /**
     * Do the conciliation in bills table
     */
    public function cociliatePayment($request, $payment_id, $bill_id, $amount_paid)
    {
        // REALIZAR POST AL ENDPOINT DE VENTAS
        try {
            DB::beginTransaction();

            $bill = new Bill();
            $bill->bill_id = $bill_id;
            $bill->payment_id = $payment_id;
            $bill->username = $request->username;
            $bill->account = $request->account;
            $bill->amount_paid = $amount_paid;
            $bill->save();

           //  = $clientService->getClient($request, $payments->client_id, false);
            DB::commit();
        }
        catch (\Exception $e){
            DB::rollback();
            return response()->json([
                "status" => 500,
                "message" => "No se ha podido registrar el pago total de la factura:"
            ], 500);
        }
    }

    public function errorExceptionSpace(\Exception $e)
    {
        if(($e->getCode() == '23P01') && ($e instanceof PDOException))
        {

        }
        return parent::errorException($e);
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
}
