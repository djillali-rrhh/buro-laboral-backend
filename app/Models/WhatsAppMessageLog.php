<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppMessageLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'whatsapp_message_logs';

    protected $fillable = [
        'to',
        'template_name',
        'message_type',
        'variables',
        'status',
        'response_json',
        'direction',
    ];

    protected $casts = [
        'variables' => 'array',
        'response_json' => 'array',
    ];
}