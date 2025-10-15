<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla de trazabilidad de Ingenia API.
 * * Reemplaza la funcionalidad de la clase legacy: models/IngeniAPI/RegistrosIngeniApi.php
 */
class RegistrosIngeniaApi extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'registros_ingenia_api';

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'curp',
        'tipo_consulta',
        'payload_request',
        'payload_response',
        'estatus',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload_request' => 'array',
        'payload_response' => 'array',
    ];
}