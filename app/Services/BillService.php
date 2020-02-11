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

    public $list;

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
        $ammounts = collect($request->amount);  // Bills amount

        // Get the payment amount
        $payments_ids = collect($request->payment_id);

        $payments = collect();
        foreach ($payments_ids as $payment_id)
        {
            $payments->push(
                collect(
                    [
                        'id' => $payment_id,
                        'amount' => Payment::findOrFail($payment_id)->only(['amount_pending'])['amount_pending']
                    ]
                )
            );
        }
        $payments = $payments->sortBy('amount');

        // Get Bills
        $contador = 0;
        $bills = collect();
        foreach ($bills_ids as $bill_id)
        {
            $bills->push([
                collect(['id'])->combine($bill_id),
                collect(['amount'])->combine($ammounts[$contador])]
            );
            $contador++;
        }

        foreach ($bills as $bill)
        {
            foreach ($payments as $payment)
            {
                $payment_id = $payment['id'];
                $pamount = Payment::findOrFail($payment_id)->only(['amount_pending'])['amount_pending'];
                if ($pamount > 0) {
                    $quantity = $pamount - $bill[1]['amount'];
                    if ( $quantity  == 0) {
                        $amount_paid = $pamount;
                        $this->cociliatePayment($request, $payment_id, $bill[0]['id'], $amount_paid);
                        $this->updatePayment($payment_id, 0);
                        $bill[1]['amount'] = 0;
                    }else if ( $quantity  < 0) {
                        $amount_paid = $pamount;
                        $this->cociliatePayment($request, $payment_id, $bill[0]['id'], $amount_paid);
                        $this->updatePayment($payment_id, 0);
                        $bill[1]['amount'] = - $quantity;
                    }else if ( $quantity  > 0){
                        $amount_paid = $bill[1]['amount'];
                        if ($amount_paid > 0) {
                            $this->cociliatePayment($request, $payment_id, $bill[0]['id'], $amount_paid);
                            $this->updatePayment($payment_id, $quantity);
                            $bill[1]['amount'] = 0;
                        }
                    }
                }
            }
        }
        return $this->successResponse('Asignación del pago realizada con éxito!');
    }

    public function cociliatePayment($request, $payment_id, $bill_id, $amount_paid)
    {
        // REALIZAR POST AL ENDPOINT DE VENTAS
        try {
            DB::beginTransaction();

            $bill = new Bill();
            $bill->payment_id = $payment_id;
            $bill->bill_id = $bill_id;
            $bill->username = $request->username;
            $bill->account = $request->account;
            $bill->amount_paid = $amount_paid;


            /*return Bill::create([
                'payment_id'    => $payment_id,
                'bill_id'       => $bill_id,
                'username'      => $request->username,
                'account'       => $request->account,
                'amount_paid'   => $amount_paid
            ]);*/

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
        return ($payment->update()) ? 1: null;
    }

    public function getPayment()
    {
        Payment::findOrFail($payments_ids);
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
