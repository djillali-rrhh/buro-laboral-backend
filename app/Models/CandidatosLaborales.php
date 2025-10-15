<?php
// app/Models/CandidatosLaborales.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidatosLaborales extends Model
{
    protected $table = 'rh_Candidatos_Laborales';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'Candidato',
        'Renglon',
        'Empresa',
        'Fecha_Ingreso',
        'Fecha_Baja',
        'Salario',
        'Estado_empleo',
        'Empresa_isela',
        'Fecha_Ingreso_isela',
        'Fecha_Baja_isela',
        'Status',
    ];

    protected $casts = [
        'Fecha_Ingreso' => 'date',
        'Fecha_Baja' => 'date',
        'Fecha_Ingreso_isela' => 'date',
        'Fecha_Baja_isela' => 'date',
        'Salario' => 'decimal:2',
        'Status' => 'integer',
    ];

    /**
     * Relación con CandidatosDatos
     */
    public function candidato()
    {
        return $this->belongsTo(CandidatosDatos::class, 'Candidato', 'Candidato');
    }

    /**
     * Scope para obtener empleos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('Status', 1);
    }

    /**
     * Scope para ordenar por renglon
     */
    public function scopeOrdenadoPorRenglon($query)
    {
        return $query->orderBy('Renglon', 'asc');
    }

    public static function getMaxRenglon(int $candidatoId): int
    {
        return self::where('Candidato', $candidatoId)
            ->max('Renglon') ?? 0;
    }
}