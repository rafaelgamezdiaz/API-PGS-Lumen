<?php


namespace App\Services;


use App\Models\Bill;
use App\Models\Payment;
use App\Traits\ApiResponser;
use function foo\func;
use Illuminate\Http\Request;
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
        $ammounts = collect($request->amount);  // Bills amount

        // Get the payment amount
        $payments_ids = collect($request->payment_id);

        // Get Payments
       /* $payments = array();
        foreach ($payments_ids as $payment_id)
        {
            array_push($payments, [
                collect(['id'])->combine($payment_id),
                Payment::findOrFail($payment_id)->only(['amount_pending'])
            ]);  //$payments->push(collect($payment_id, Payment::findOrFail($payment_id)->only(['amount_pending'])));
        }

        return $payments;*/
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

            //$payments->push(collect($payment_id, Payment::findOrFail($payment_id)->only(['amount_pending'])));
        }
        $payments = $payments->sortBy('amount');
        // Get the bill cost ordered by amount (from lower to high value)
       // $bills_costs = $bills_ids->combine($ammounts);


        // Get Bills
        $contador = 0;
        $bills = collect();
        foreach ($bills_ids as $bill_id)
        {
            $bills->push([
                collect(['id'])->combine($bill_id),
                collect(['amount'])->combine($ammounts[$contador])]
            );
            $contador++; //$payments->push(collect($payment_id, Payment::findOrFail($payment_id)->only(['amount_pending'])));
        }


        // Process (Loop by bills)
        /*$bills->each(function($bill) use($request) {
             $this->byPayment($request, $bill);
        });*/


        foreach ($bills as $bill)
        {
            $payments->each(function($payments) use($request, $bill){
                $quantity = $payments['amount'] - $bill[1]['amount'];
                if ( $quantity  == 0) {
                    $this->cociliatePayment($request, $payments['id'], $bill);

                    // Actualizo el amount pending de ese pago a 0
                    $payments['amount'] = 0; // $payment[1]['amount_pending'] = 0;

                }else if ( $quantity  < 0) {
                    $this->cociliatePayment($request, $payments['id'], $bill);

                    // Actualizo el amount pending de ese pago a 0
                    $payments['amount'] = 0; // $this->payments[1]['amount_pending'] = 0;

                }else if ( $quantity  > 0){
                    $payments['amount'] = $quantity;
                }
            });

           /* for ($i = 0; $i < count($payments); $i++){




            }*/

        }
        /*foreach ($bills as $bill)
        {
            for ($i = 0; $i < count($payments); $i++){
                $quantity = $payments[$i][1]['amount_pending'] - $bill[1]['amount'];

                if ( $quantity  == 0) {

                    $this->cociliatePayment($request, $payments[$i][0]['id'], $bill);

                    // Actualizo el amount pending de ese pago a 0
                    $payments[$i][1]['amount_pending'] = 0; // $payment[1]['amount_pending'] = 0;

                }else if ( $quantity  < 0) {

                    $this->cociliatePayment($request, $payments[$i][0]['id'], $bill);

                    // Actualizo el amount pending de ese pago a 0
                    $payments[$i][1]['amount_pending'] = 0; // $this->payments[1]['amount_pending'] = 0;

                }else if ( $quantity  > 0){
                    $payments[$i][1]['amount_pending'] = $quantity;
                }

            }

        }*/

        return $payments;


        /*$bills_costs->each(function($bills_costs) use($request, $payments) {
            $this->byPayment($request, $payments, $bills_costs);
        });*/


        return $this->successResponse('Asignación del pago realizada con éxito!');
    }

    public function byPayment($request, $bill)
    {
        // Loop by payments
        $this->payments->each(function($payment) use($request, $bill) {



        });

    }

    public function cociliatePayment($request, $payment, $bill)
    {
        // REALIZAR POST AL ENDPOINT DE VENTAS

        return Bill::create([
            'payment_id'    => $payment,
            'bill_id'       => $bill[0]['id'],
            'username'      => $request->username,
            'account'       => $request->account
        ]);
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
