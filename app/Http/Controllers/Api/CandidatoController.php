<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidato;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CandidatoController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource with related work history.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Candidato::with('laborales');

        if ($request->has('nombre_completo')) {
            $nombreCompleto = $request->query('nombre_completo');
            $query->where(DB::raw("CONCAT(Nombres, ' ', Apellido_Paterno, ' ', Apellido_Materno)"), 'LIKE', "%{$nombreCompleto}%");
        }

        $fillableFields = (new Candidato())->getFillable();

        foreach ($request->query() as $param => $value) {
            if ($param === 'nombre_completo' || empty($value)) {
                continue;
            }

            if (in_array($param, $fillableFields)) {
                $query->where($param, 'LIKE', "%{$value}%");
            }
        }

        $candidatos = $query->paginate();

        return $this->successResponse($candidatos);
    }

    /**
     * Display the specified resource with related work history.
     */
    public function show(Candidato $candidato)
    {
        $candidato->load('laborales');
        
        return $this->successResponse($candidato);
    }
}
