<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestigacionLaboral extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'Investigacion_Laboral';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'Candidato';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'Proporciono_Datos_Empleos',
        'Motivo_No_Proporciono_Datos',
        'Demanda_Laboral',
        'Motivo_Demanda',
        'No_Empleos',
        'Tiempo_Promedio_Empleos',
        'Circunstancias_Laborales',
        'Sindicalizado',
        'Sindicato',
        'Comite_Sindical',
        'Puesto_Sindical',
        'Funciones_Sindicato',
        'Tiempo_Sindicato',
        'Trabajo_Ternium',
        'Alta_Ternium',
        'Veto_Ternium',
        'Positivo_Antidoping',
        'Sustancia_Antidoping',
        'Accidentes_Empresa',
        'Abandono_Unidad',
        'Familiar_Empresa',
        'Reingreso',
        'Laborando',
        'Que_Empresa',
        'Enterado_Puesto',
        'Comparte_Datos',
        'Estabilidad_laboral',
        'Numero_Registros',
        'Periodo_Inactividad',
        'Periodo',
        'Informacion_Compartida',
        'Tipo_Informacion_Compartida',
        'Cumplimiento_Validacion',
        'Justificacion_Cumplimiento_Validacion',
        'Ausentismo',
        'Abandono',
        'Desempeno',
        'Omitio_Empleo',
        'Empleo_mas',
        'Familiar_Empresa_relacion',
        'Familiar_Empresa_relacion_puesto',
    ];
}
