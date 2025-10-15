<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidatoLaboral extends Model
{
    use HasFactory;

    protected $table = 'rh_Candidatos_Laborales';
    // Nota: El modelo legacy usa Candidato y Renglon como identificadores
    // pero para Eloquent usaremos la configuración predeterminada.
    public $timestamps = false; // Desactivar timestamps, ya que el código legacy no los usa.

    protected $fillable = [
        'Candidato',
        'Renglon',
        'Empresa',
        'Giro',
        'Domicilio',
        'Telefono',
        'Fecha_Ingreso',
        'Fecha_Baja',
        'Puesto_Inicial',
        'Puesto_Final',
        'Jefe',
        'Puesto_Jefe',
        'Motivo_Separacion',
        'Comentarios',
        'Recontratable',
        'Recontratable_PorQue',
        'Informante',
        'Calif',
        'Dopaje',
        'Sindicalizado',
        'Sindicato',
        'Comite_Sindical',
        'Puesto_Sindical',
        'Funciones_Sindicato',
        'Tiempo_Sindicato',
        'Sitio_Web',
        'Correo',
        'Puesto_Informante',
        'Razon_Social',
        'Tipo_Recision',
        'Tipo_Empleo',
        'Comentario_Ejecutivo',
        'Estado_empleo',
        'Salario',
        'Estado',
        'Ciudad',
        'Codigo_Postal',
        'Empresa_isela',
        'Fecha_Ingreso_isela',
        'Fecha_Baja_isela',
        'status',
        'griver_1',
        'griver_2',
        'griver_3',
        'griver_4',
        'Tipo_Unidad_Operada',
        'Tipo_Carga_Transportada',
        'prellenado_candidato',
        'prellenado_entrevistador',
        'Validacion_Datos_Cooperacion',
        // Campos relacionados con DOT (Alcohol/Drogas)
        'Alcohol_Resultado_Mayor004', 
        'Drogas_Positivo', 
        'Se_Nego_Prueba', 
        'Violacion_Regulaciones_DOT', 
        'Reporte_Violacion_Empleador_Previo',
        'Completo_Proceso_Retorno_Servicio',
    ];

    protected $casts = [
        'Fecha_Ingreso' => 'date',
        'Fecha_Baja' => 'date',
        'Fecha_Ingreso_isela' => 'date',
        'Fecha_Baja_isela' => 'date',
        'Recontratable' => 'boolean',
        'Calif' => 'integer',
        'Dopaje' => 'integer',
        'Sindicalizado' => 'boolean',
    ];

    /**
     * Nota de migración:
     * Métodos complejos como getLaboralesPorCandidato() y los que usan stored procedures (CALL)
     * deben ser reescritos usando el Query Builder de Eloquent o trasladados a un Repository/Service 
     * dedicado para mantener limpio el modelo.
     */
}
