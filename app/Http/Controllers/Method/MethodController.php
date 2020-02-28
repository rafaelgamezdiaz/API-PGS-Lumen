<?php

namespace App\Http\Controllers\Method;

use App\Http\Controllers\BaseController;
use App\Models\Method;
use App\Services\MethodService;


class MethodController extends BaseController
{
    public function index( MethodService $methodService )
    {
        return $methodService->index();
    }

    public function show($id, MethodService $methodService)
    {
        return $methodService->show($id);
    }
}
