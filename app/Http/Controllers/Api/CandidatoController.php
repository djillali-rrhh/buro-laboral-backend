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
     * Display a listing of the resource.
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Candidato::query()->with(['laborales', 'investigacionLaboral']);

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

        if ($candidatos->isEmpty()) {
            return $this->successResponse($candidatos, 'No se encontraron candidatos con los criterios de búsqueda.');
        }

        return $this->successResponse($candidatos);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $candidato = Candidato::find($id);

        if (!$candidato) {
            return $this->errorResponse('El candidato solicitado no existe.', 404);
        }

        $candidato->load(['laborales', 'investigacionLaboral']);

        return $this->successResponse($candidato);
    }
}

