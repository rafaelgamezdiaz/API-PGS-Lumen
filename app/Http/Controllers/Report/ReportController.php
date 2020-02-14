<?php
/**
 * Created by PhpStorm.
 * User: develop
 * Date: 13/03/19
 * Time: 02:40 PM
 */

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
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
    public function automatic(Request $request)
    {
        $index= [$request->index];
       // $info = $this->sortByDate([$request->data]);
        $info = [$request->data];
        $report = (new ReportService());
        $report->indexPerSheet($index);
        $report->dataPerSheet($info);
        $report->data($request->data);
        $report->index($request->index);
        $report->external();
        $report->transmissionRaw();

        // Load Logo
        $user = $request->get('user')->user;
        $report->getAccountInfo($user->current_account);

        return $report->report("automatic","Pagos","",null,false,2);
    }

}
