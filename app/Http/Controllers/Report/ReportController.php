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
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    use ApiResponser;
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
            'Codigo empresa'        =>'contract',
            'Tipo de pago'          =>'type',
            'Cliente'               =>'client',
            'Documento'             =>'reference',
            'Cobrador'              =>'username',
            'Monto del Pago'        =>'amount',
            'Forma de pago'         =>'method',
            'Monto Disponible'      =>'amount_pending',
            'Fecha/Hora de pago'    =>'created_at',
            'Estado'                =>'status'
        ];
        $payments = $paymentService->dataForReport($request, $clientService, $userService);

        if ($payments['status'] == 500) {
            return $this->errorMessage('Error de conexion. No es posible generar el reporte');
        }
        $payments = $payments['list'];
        $payments = collect($payments)->recursive();

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
        $item = 1;
        foreach ($payments as $payment){
                $contract = isset($payment['client']['contract']) ? $payment['client']['contract'] : "";
                $commerce_name = isset($payment['client']['commerce_name']) ? $payment['client']['commerce_name'] : "";
                $user = isset($payment['user']['name']) ? $payment['user']['name'] : "";
                array_push($table, [
                    'id'            => $item,
                    'type'          => $payment['type']['type'],
                    'client'        => $commerce_name,
                    'contract'      => $contract,
                    'reference'     => $payment['reference'],
                    'username'      => $user,
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
