<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Eloquent para la tabla Busqueda_RAL.
 * Representa una búsqueda realizada en el sistema, registrando los datos de entrada
 * como CURP, nombres y apellidos, y sirviendo como punto de anclaje para los
 * expedientes encontrados.
 *
 * @property int $ID Llave primaria autoincremental.
 * @property string $Nombres Nombres de la persona buscada.
 * @property string $Apellidos Apellidos de la persona buscada.
 * @property string $CURP CURP utilizada en la búsqueda.
 * @property \Illuminate\Support\Carbon $Fecha Fecha en que se realizó la búsqueda.
 * @property string $Creado Origen de la creación del registro (ej. 'Sistema API').
 * @property int $Candidato ID del candidato asociado a la búsqueda.
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|ExpedienteRal[] $expedientes La colección de expedientes asociados a esta búsqueda.
 * @package App\Models
 */
class BusquedaRal extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'Busqueda_RAL';

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
        'Nombres',
        'Apellidos',
        'CURP',
        'Fecha',
        'Creado',
        'Candidato',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'Fecha' => 'datetime'
    ];

    /**
     * Define la relación "uno a muchos": una búsqueda puede tener muchos expedientes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function expedientes(): HasMany
    {
        return $this->hasMany(ExpedienteRal::class, 'ID_Busqueda_RAL', 'ID');
    }
}
