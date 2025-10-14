<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent para la tabla Acuerdos_RAL.
 * Gestiona los acuerdos o resoluciones individuales dentro de un expediente judicial.
 *
 * Reemplaza la clase legacy /poderjduciales/Acuerdos_RAL.php
 *
 * @property int $ID Llave primaria autoincremental.
 * @property \Illuminate\Support\Carbon $Fecha Fecha en que se emitió el acuerdo.
 * @property string $Acuerdo El texto o descripción del acuerdo.
 * @property string $Tipo El tipo de acuerdo (ej. 'ACUERDO', 'SENTENCIA').
 * @property string $Actor El nombre de la parte actora mencionado en el acuerdo.
 * @property string $Demandado El nombre de la parte demandada mencionado en el acuerdo.
 * @property int $ID_Expediente_RAL Llave foránea que relaciona con la tabla Expediente_Ral.
 * @property int|null $score_laboral Puntuación relacionada con aspectos laborales (si aplica).
 *
 * @property-read ExpedienteRal $expediente La relación que carga el modelo del expediente al que pertenece este acuerdo.
 * @package App\Models
 */
class AcuerdoRal extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'Acuerdos_RAL';

    /**
     * La llave primaria para el modelo.
     *
     * @var string
     */
    protected $primaryKey = 'ID';

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
        'Fecha',
        'Acuerdo',
        'Tipo',
        'Actor',
        'Demandado',
        'ID_Expediente_RAL',
        'score_laboral',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'Fecha' => 'datetime',
    ];

    /**
     * Define la relación inversa: un acuerdo pertenece a un expediente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(ExpedienteRal::class, 'ID_Expediente_RAL', 'ID');
    }
}
