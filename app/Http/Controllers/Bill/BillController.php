<?php

namespace App\Http\Controllers\Bill;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Services\BillService;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function index(Request $request, BillService $billService)
    {
        return $billService->index($request);
    }

    public function store(Request $request, Bill $bill, BillService $billService)
    {
        return $billService->store($request, $bill);
    }
}
