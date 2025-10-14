<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrosPalenca extends Model
{
    use HasFactory;
    /**
     * The database connection that should be used by the model.
     * 
     * @var string
     */
    protected $connection = 'sqlsrv_logs';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'registros_palenca';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     * SQL Server no usa created_at/updated_at por defecto
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'Candidato',
        'CURP',
        'Estatus',
        'Acceso',
        'Respuesta_json',
        'Fecha_Registro',
        'AccountId',
        'JsonCuenta',
        'JsonEmployment',
        'JsonProfile',
        'JsonEnvio',
        'JsonEmploymentHistory',
        'ModifiedAt',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'Estatus' => 'boolean',
        'Fecha_Registro' => 'datetime',
        'ModifiedAt' => 'datetime',
        'JsonCuenta' => 'array',
        'JsonEmployment' => 'array',
        'JsonProfile' => 'array',
        'JsonEmploymentHistory' => 'array',
        'Respuesta_json' => 'array',
        'JsonEnvio' => 'array',
    ];

    /**
     * Boot method to set default values
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->Fecha_Registro = $model->Fecha_Registro ?? now();
            $model->ModifiedAt = now();
        });

        static::updating(function ($model) {
            $model->ModifiedAt = now();
        });
    }
}