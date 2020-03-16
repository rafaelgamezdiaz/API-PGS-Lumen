<?php
/**
 * Created by PhpStorm.
 * User: develop
 * Date: 13/03/19
 * Time: 02:40 PM
 */

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\ClientService;
use App\Services\PaymentService;
use App\Services\ReportService;
use App\Services\UserService;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    /**
     * The service to consume the client service
     */
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     *  Returns the Payments Report
     */
    public function automatic(Request $request,
                              PaymentService $paymentService,
                              ClientService $clientService,
                              UserService $userService)
    {
        $index = [
            'ITEM'                  =>'id',
            'Tipo de pago'          =>'type',
            'Cliente'               =>'client',
            'Documento'             =>'reference',
            'Usuario'               =>'username',
            'Monto del Pago'        =>'amount',
            'Forma de pago'         =>'method',
            'Monto Disponible'      =>'amount_pending',
            'Fecha/Hora de pago'    =>'created_at',
            'Estado'                =>'status'
        ];
        $payments = $paymentService->index($request, $clientService, $userService, true);
        $info = $this->buildReportTable($payments);
        $report = (new ReportService());
        $report->indexPerSheet($index);
        $report->dataPerSheet($info);
        $report->data($info);
        $report->index($index);
        $report->external();
        $report->transmissionRaw();

        // Load Logo
        $user = $request->get('user')->user;
        $report->getAccountInfo($user->current_account);
        $report->username($user->username);

        return $report->report("automatic","Pagos","",null,false,2);
    }

    private function buildReportTable($payments){
        $table = array();
        $payments = collect($payments)->recursive();
        $item = 1;
        foreach ($payments as $payment){
                array_push($table, [
                    'id'            => $item,
                    'type'          => $payment['type']['type'],
                    'client'        => $payment['client']['commerce_name'],
                    'reference'     => $payment['reference'],
                    'username'      => $payment['user']['name'],
                    'amount'        => $payment['amount'],
                    'method'        => $payment['method']['method'],
                    'amount_pending' => $payment['amount_pending'],
                    'created_at'    => $payment['created_at'],
                    'status'      => $payment['status']
                ]);
                $item++;
        }
        return $table;
    }

}
