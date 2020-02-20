<?php

namespace App\Http\Controllers\Method;

use App\Http\Controllers\BaseController;
use App\Models\Method;
use App\Models\Payment;

class MethodController extends BaseController
{
    public function index()
    {
        $methods = Method::all();
        return $this->successResponse("Payment Methods", $methods);
    }
}
