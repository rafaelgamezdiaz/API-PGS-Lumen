<?php


namespace App\Services;

use App\Models\Method;
use App\Traits\ApiResponser;
use Laravel\Lumen\Routing\ProvidesConvenienceMethods;

class MethodService extends BaseService
{
    use ApiResponser, ProvidesConvenienceMethods;

    public function index()
    {
        $methods = Method::all();
        return $this->successResponse("Payment Methods", $methods);
    }

    public function show($id)
    {
        $method = Method::findOrFail($id);
        return $method;
    }

}
