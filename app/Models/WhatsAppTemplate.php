<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     * Esta línea es la solución clave al problema.
     *
     * @var string
     */
    protected $table = 'whatsapp_templates';

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'language',
        'category',
        'status',
        'variables', // Corresponde a los 'components' de la API
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'variables' => 'array',
    ];
}

