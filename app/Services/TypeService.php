<?php


namespace App\Services;

use App\Models\Method;
use App\Models\Type;
use App\Traits\ApiResponser;
use Laravel\Lumen\Routing\ProvidesConvenienceMethods;

class TypeService extends BaseService
{
    use ApiResponser, ProvidesConvenienceMethods;

    public function index()
    {
        $types = Type::all();
        return $this->successResponse("Payment Types", $types);
    }

    public function show($id)
    {
        $type = Type::findOrFail($id);
        return $type;
    }

}
