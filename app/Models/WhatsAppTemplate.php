<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'language',
        'variables',
        'status',
    ];

    protected $casts = [
        'variables' => 'array',
    ];
}