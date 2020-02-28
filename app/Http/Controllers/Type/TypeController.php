<?php

namespace App\Http\Controllers\Type;

use App\Http\Controllers\Controller;
use App\Models\Type;
use App\Services\TypeService;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    use ApiResponser;

    /**
     * List all Payment methods
     */
    public function index(TypeService $typeService)
    {
        return $typeService->index();
    }

    /**
     * Get a payment method info
     */
    public function show($id, TypeService $typeService)
    {
        return $typeService->show($id);
    }
}
