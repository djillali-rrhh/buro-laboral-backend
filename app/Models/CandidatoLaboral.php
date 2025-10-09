<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidatoLaboral extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rh_Candidatos_Laborales';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
        'pregunta_noviable_1',
        'pregunta_noviable_2',
        'pregunta_noviable_3',
        'pregunta_noviable_4',
        'pregunta_noviable_5',
        'pregunta_noviable_6',
        'pregunta_viableobs_1',
        'pregunta_viableobs_2',
        'pregunta_viableobs_3',
        'pregunta_viableobs_4',
        'pregunta_viable_1',
        'pregunta_viable_2',
        'Estado_empleo',
        'Salario',
        'Ejecutivo_Referencia',
        'Comentario_Mensaje',
        'ID_Documento',
        'Fecha_Envio',
        'Tipo_Empleo',
        'Comentario_Ejecutivo',
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
        'fecha_respuesta',
        'prellenado_candidato',
        'prellenado_entrevistador',
        'Formulario_ServiciosTerrestres',
        'Fecha_Modificacion',
        'Datos_Cooperacion',
        'Tipo_Unidad_Operada',
        'Tipo_Carga_Transportada',
        'Validacion_DatosCooperacion',
        'correos_formulario',
        'toques',
        'Alcohol_Resultado_Mayor004',
        'Drogas_Positivo',
        'Se_Nego_Prueba',
        'Violacion_Regulaciones_DOT',
        'Reporte_Violacion_Empleador_Previo',
        'Completo_Proceso_Retorno_Servicio',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
}
