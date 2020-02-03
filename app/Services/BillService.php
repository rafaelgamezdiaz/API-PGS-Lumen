<?php


namespace App\Services;


use App\Models\Bill;
use App\Models\Payment;
use App\Traits\ApiResponser;
use Laravel\Lumen\Routing\ProvidesConvenienceMethods;

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

    public function store($request, $bill)
    {
        $this->validate($request, $bill->rules());
        $bills_ids = collect($request->bill_id);
        $ammounts = collect($request->amount);

        // Get the payment amount
        $payments_ids = collect($request->payment_id);

        // Get the bill cost ordered by amount (from lower to high value)
        $bills_costs = $bills_ids->combine($ammounts)->sort();
        $bills_costs->values()->all();



        $bills_costs->each(function($bills_costs) use($request, $payments_ids) {

            // By Payments loop
            $this->byPayment($request, $payments_ids, $bills_costs);

        });
        /*if ($this->errors > 0) {
            return $this->errorMessage('Ha ocurrido un error al intentar guardar la asignación.');
        }*/

        return $this->successResponse('Asignación del pago realizada con éxito!');
    }

    public function byPayment($request, $payments_ids, $bill_cost)
    {
        $payments_ids->each(function($payments_ids) use($request, $bills_costs) {
            $payment_amount = Payment::findOrFail($payments_ids)
                                     ->get('amount_pending');
            $quantity = $payment_amount - $bill_cost->value();

            return Bill::create([
                'payment_id'    => $payment_id,
                'bill_id'       => $bill_id,
                'amount'        => $request->amount,
                'username'      => $request->username,
                'account'       => $request->account
            ]);
        });
    }

    public function show($request, $id){

    }

    /**
     * Update the Payment
     */
    public function update($request, $id)
    {

    }

    public function destroy($id)
    {

    }
}
