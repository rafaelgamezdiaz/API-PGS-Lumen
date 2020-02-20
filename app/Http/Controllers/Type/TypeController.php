<?php

namespace App\Http\Controllers\Type;

use App\Http\Controllers\Controller;
use App\Models\Type;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    use ApiResponser;

    public function index()
    {
        $types = Type::all();
        return $this->successResponse("Payment Type", $types);
    }
}
