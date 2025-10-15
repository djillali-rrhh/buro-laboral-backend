<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrosNubariumApi extends Model
{
    protected $table = 'registros_nubarium_api';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'payload_request',
        'payload_response',
        'estatus',
    ];

    protected $casts = [
        'payload_request' => 'array',
        'payload_response' => 'array',
    ];
}
