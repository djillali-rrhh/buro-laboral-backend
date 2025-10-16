<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent Model for the 'registros_apimarket_api' table.
 *
 * This model represents a log entry for a single API call to ApiMarket services.
 * It is configured to not use Laravel's default 'created_at' and 'updated_at' timestamps,
 * opting for a custom 'fecha_registro' field instead.
 *
 * @property int $id The primary key.
 * @property string $servicio The service that was called (e.g., 'obtener-nss').
 * @property string|null $curp The CURP used in the request, if any.
 * @property string|null $nss The NSS used in the request, if any.
 * @property string $payload_request The JSON-encoded request payload.
 * @property string $payload_response The JSON-encoded response payload.
 * @property string $estatus The final status of the call (e.g., 'exitoso', 'fallido').
 * @property string $fecha_registro The timestamp when the record was created.
 */
class RegistrosApiMarket extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'registros_apimarket_api';

    /**
     * Indicates if the model should be timestamped.
     * Set to false because the migration uses a custom 'fecha_registro' column.
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
        'servicio',
        'curp',
        'nss',
        'payload_request',
        'payload_response',
        'estatus',
        'fecha_registro',
    ];
}

