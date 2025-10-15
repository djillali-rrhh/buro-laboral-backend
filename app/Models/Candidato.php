<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidato extends Model
{
    use HasFactory;

    protected $table = 'rh_Candidatos_Datos';
    protected $primaryKey = 'Candidato';

    protected $fillable = [
        'Nombres',
        'Apellido_Paterno',
        'Apellido_Materno',
        'Nacimiento',
        'Lugar_Nacimiento',
        'Sexo',
        'Fecha_Matrimonio',
        'Nacionalidad',
        'Estado_Civil',
        'Hijos',
        'Vive_con',
        'Telefono_fijo',
        'Correos',
        'Celular',
        'Otro_Contacto',
        'Desplazamiento',
        'Actividad_Adicional',
        'Continuar_estudios',
        'Cual_Estudio',
        'Sindicato',
        'Cual_Sindicato',
        'Aspiracion',
        'Espera_Empresa',
        'Como_entero',
        'Accidente',
        'Accidente_Motivo',
        'Demanda_Laboral',
        'Religion',
        'Pasatiempos',
        'Afiliacion_politica',
        'Afiliacion_Club',
        'CURP',
        'IMSS',
        'RFC',
        'Domicilio',
        'Comentario_Demanda',
        'Tiempo',
        'Cual_Actividad',
        'Comentario_Contacto',
        'Edad',
        'Cualidades',
        'Habilidades',
        'Facebook',
        'Linkedin',
        'Numero_Licencia',
        'Aprobacion',
        'Fecha_Aprobacion',
        'Usuario_Cliente',
        'Usuario_Comentario',
        'semanasIMMSI_SSSTE',
        'zona_horaria',
        'Fecha_BC',
        'Folio_BC',
        'Fecha_Subida_BC',
        'lada',
        'Promedio_Academico',
        'Estado_Residencia',
    ];

/**
     * Los atributos que deben ser añadidos a las representaciones de array/JSON del modelo.
     *
     * @var array
     */
    protected $appends = [
        'sexo_texto'
    ];


    /**
     * Crea el atributo virtual "sexo_texto" basado en el valor de "Sexo".
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function sexoTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->Sexo) {
                '98' => 'Masculino',
                '99' => 'Femenino',
                default => 'No especificado',
            },
        );
    }


    /**
     * Define la relación "uno a muchos" con los datos laborales del candidato.
     */
    public function laborales(): HasMany
    {
        return $this->hasMany(CandidatoLaboral::class, 'Candidato', 'Candidato');
    }

    /**
     * Define la relación "uno a muchos" con la investigación laboral del candidato.
     */
    public function investigacionLaboral(): HasMany
    {
        return $this->hasMany(InvestigacionLaboral::class, 'Candidato', 'Candidato');
    }
}