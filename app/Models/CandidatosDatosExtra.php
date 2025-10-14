<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidatosDatosExtra extends Model
{
    protected $table = 'rh_Candidatos_Datos_Extra';
    protected $primaryKey = 'Candidato';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'Candidato',
        'imss',
        'Semanas_Cotizadas',
        'Semanas_Descontadas_IMSS',
        'Semanas_Reintegradas',
        'Total_Semanas_Cotizadas',
        'Numero_Empleos',
    ];

    /**
     * Relación con CandidatosDatos
     */
    public function candidato()
    {
        return $this->belongsTo(CandidatosDatos::class, 'Candidato', 'Candidato');
    }
}