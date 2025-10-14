<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent para la tabla Expediente_Ral_partes.
 * Gestiona las partes (actores, demandados) asociadas a un expediente judicial.
 *
 * Reemplaza la clase legacy /poderjduciales/Expediente_RAL_Partes.php
 *
 * @property int $id Llave primaria autoincremental.
 * @property int $ID_Expediente Llave foránea que relaciona con la tabla Expediente_Ral.
 * @property string $rol El papel de la parte en el expediente (ej. 'actor', 'demandado').
 * @property string $nombre_parte El nombre original de la parte como aparece en el expediente.
 * @property string $nombre_normalizado El nombre de la parte normalizado (mayúsculas, sin acentos).
 * @property string $Tipo_parte El tipo de parte (ej. 'Persona', 'Empresa').
 * @property int $Es_Candidato Bandera (1 o 0) que indica si esta parte es el candidato principal de la búsqueda.
 * @property int $score Puntuación de coincidencia del nombre de la parte con el candidato.
 * @property int $score_laboral Puntuación relacionada con aspectos laborales (si aplica).
 *
 * @property-read ExpedienteRal $expediente La relación que carga el modelo del expediente al que pertenece esta parte.
 * @package App\Models
 */
class ExpedienteRalParte extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'Expediente_Ral_partes';

    /**
     * La llave primaria para el modelo.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indica si el modelo debe tener timestamps (created_at, updated_at).
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ID_Expediente',
        'rol',
        'nombre_parte',
        'nombre_normalizado',
        'Tipo_parte',
        'Es_Candidato',
        'score',
        'score_laboral',
    ];

    /**
     * Define la relación inversa: una parte pertenece a un expediente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(ExpedienteRal::class, 'ID_Expediente', 'ID');
    }
}
