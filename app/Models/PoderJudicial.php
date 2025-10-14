<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent para la tabla de logs de la API (registros_api_poder_judicial).
 * Gestiona los registros de las consultas realizadas a la API del Poder Judicial.
 *
 * Reemplaza la clase legacy /poderjduciales/Poder_Judicial.php
 *
 * @property int $id Llave primaria autoincremental.
 * @property int $candidato ID del candidato asociado al registro.
 * @property string $curp CURP que fue consultada.
 * @property string $nombres Nombres asociados a la consulta.
 * @property array $respuesta_json La respuesta completa de la API, almacenada como JSON.
 * @property \Illuminate\Support\Carbon $fecha_registro Fecha y hora de creación del registro.
 * @package App\Models
 */
class PoderJudicial extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'registros_api_poder_judicial';

    /**
     * La llave primaria para el modelo.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * El nombre de la columna "created at".
     *
     * @var string
     */
    const CREATED_AT = 'fecha_registro';

    /**
     * El nombre de la columna "updated at". Se anula porque la tabla no la utiliza.
     *
     * @var null
     */
    const UPDATED_AT = null;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'candidato',
        'curp',
        'nombres',
        'respuesta_json',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_registro' => 'datetime',
        'respuesta_json' => 'array',
    ];
}
