<?php
// app/Models/CandidatosDatos.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidatosDatos extends Model
{
    protected $table = 'rh_Candidatos_Datos';
    protected $primaryKey = 'Candidato';
    public $timestamps = false;

    protected $fillable = [
        'Candidato',
        'IMSS',
        'Nombre',
        'Paterno',
        'Materno',
        'CURP',
        'RFC',
        'Fecha_Nacimiento',
    ];

    /**
     * Relación con RegistrosPalenca
     */
    public function registrosPalenca()
    {
        return $this->hasMany(RegistrosPalenca::class, 'Candidato', 'Candidato');
    }

    /**
     * Relación con CandidatosDatosExtra
     */
    public function datosExtra()
    {
        return $this->hasOne(CandidatosDatosExtra::class, 'Candidato', 'Candidato');
    }

    /**
     * Relación con CandidatosLaborales
     */
    public function historialLaboral()
    {
        return $this->hasMany(CandidatosLaborales::class, 'Candidato', 'Candidato');
    }
}