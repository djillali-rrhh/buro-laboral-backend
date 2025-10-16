<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrosApiMarket extends Model
{
    use HasFactory;

    protected $table = 'registros_apimarket_api';

    protected $fillable = [
        'servicio',
        'curp',
        'nss',
        'payload_request',
        'payload_response',
        'estatus',
    ];
}

