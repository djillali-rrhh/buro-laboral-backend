<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Eloquent para la tabla Expediente_RAL.
 * Reemplaza la clase legacy /poderjduciales/Expediente_RAL.php
 */
class ExpedienteRal extends Model
{
    use HasFactory;

    protected $table = 'Expediente_RAL';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    /**
     * Atributos asignables masivamente, extraídos de la clase legacy.
     */
    protected $fillable = [
        'Fecha',
        'Num_Expediente',
        'Anio',
        'Estado',
        'ciudad', // Nota: Laravel prefiere snake_case (ciudad -> ciudad)
        'Juzgado',
        'Op',
        'Toca',
        'Actor',
        'Demandado',
        'Tipo',
        'ID_Busqueda_RAL',
        'actos_reclamados',
        'actos_reclamados_especificos',
        'expediente_origen',
        'fecha_sentencia',
        'materia',
        'resolucion',
        'HomonymScore',
        'rol_detectado',
        'score',
        'fecha_ultima_actualizacion',
        'curp_entexto',
    ];

    protected $casts = [
        'Fecha' => 'datetime',
        'fecha_sentencia' => 'datetime',
        'fecha_ultima_actualizacion' => 'datetime',
    ];

    /**
     * Relación inversa "pertenece a" con la búsqueda.
     */
    public function busqueda(): BelongsTo
    {
        return $this->belongsTo(BusquedaRal::class, 'ID_Busqueda_RAL', 'ID');
    }

    /**
     * Relación "uno a muchos" con las partes del expediente.
     */
    public function partes(): HasMany
    {
        return $this->hasMany(ExpedienteRalParte::class, 'ID_Expediente', 'ID');
    }

    /**
     * Relación "uno a muchos" con los acuerdos del expediente.
     * Reemplaza `getAcuerdosPorExpediente()`.
     */
    public function acuerdos(): HasMany
    {
        return $this->hasMany(AcuerdoRal::class, 'ID_Expediente_RAL', 'ID');
    }
}