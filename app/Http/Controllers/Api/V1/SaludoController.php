<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

class SaludoController extends Controller
{
    use ApiResponse;

    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke()
    {
        return $this->successResponse([], 'Saludo');
    }
}

