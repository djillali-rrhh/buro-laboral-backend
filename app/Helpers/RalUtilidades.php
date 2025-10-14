<?php

namespace App\Helpers;

/**
 * Provee un conjunto de funciones estáticas para operaciones comunes.
 * Incluye normalización de texto, validaciones de CURP/RFC, y extracción de datos.
 *
 * @package App\Helpers
 */
class RalUtilidades
{
    /**
     * Normaliza un texto: lo convierte a mayúsculas, quita acentos y caracteres especiales.
     *
     * @param string|null $texto El texto de entrada a normalizar.
     * @return string El texto normalizado. Devuelve una cadena vacía si la entrada es nula.
     */
    public static function norm_text(?string $texto): string
    {
        if (is_null($texto)) return '';
        $texto = strtoupper($texto);
        $texto = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'N'], $texto);
        return preg_replace('/[^A-Z0-9\s]/', '', $texto);
    }

    /**
     * Convierte el nombre completo de un estado a su abreviatura estandarizada de dos letras.
     *
     * @param string $estado El nombre completo del estado.
     * @return string La abreviatura del estado en minúsculas (ej. 'tm' para Tamaulipas).
     */
    public static function norm_estado(string $estado): string
    {
        $mapaEstados = [
            'AGUASCALIENTES' => 'ag',
            'BAJA CALIFORNIA' => 'bc',
            'BAJA CALIFORNIA SUR' => 'bs',
            'CAMPECHE' => 'cc',
            'CHIAPAS' => 'cs',
            'CHIHUAHUA' => 'ch',
            'CIUDAD DE MEXICO' => 'cdmx',
            'COAHUILA' => 'cm',
            'COLIMA' => 'cl',
            'DURANGO' => 'dg',
            'ESTADO DE MEXICO' => 'me',
            'GUANAJUATO' => 'gt',
            'GUERRERO' => 'gr',
            'HIDALGO' => 'hg',
            'JALISCO' => 'ja',
            'MICHOACAN' => 'mn',
            'MORELOS' => 'ms',
            'NAYARIT' => 'nt',
            'NUEVO LEON' => 'nl',
            'OAXACA' => 'oc',
            'PUEBLA' => 'pl',
            'QUERETARO' => 'qt',
            'QUINTANA ROO' => 'qr',
            'SAN LUIS POTOSI' => 'sp',
            'SINALOA' => 'si',
            'SONORA' => 'sr',
            'TABASCO' => 'tc',
            'TAMAULIPAS' => 'tm',
            'TLAXCALA' => 'tl',
            'VERACRUZ' => 'vz',
            'YUCATAN' => 'yn',
            'ZACATECAS' => 'zs',
        ];
        $estadoNormalizado = self::norm_text($estado);
        return $mapaEstados[$estadoNormalizado] ?? strtolower($estado);
    }

    /**
     * Calcula un puntaje de coincidencia simple basado en los primeros 4 caracteres de un nombre.
     *
     * @param string $nombreCompleto El nombre completo de la persona a buscar.
     * @param array  $partes Un array de arrays, donde cada subarray debe contener una llave 'nombre'.
     * @return int Devuelve 100 si se encuentra una coincidencia, de lo contrario 0.
     */
    public static function score_coincidencia(string $nombreCompleto, array $partes): int
    {
        if (empty($partes) || empty($nombreCompleto)) return 0;
        $nombreBuscado = self::norm_text(substr($nombreCompleto, 0, 4));
        $maxScore = 0;
        foreach ($partes as $parte) {
            if (empty($parte['nombre'])) continue;
            $nombreParte = self::norm_text($parte['nombre']);
            if (str_contains($nombreParte, $nombreBuscado)) {
                $currentScore = 100;
                if ($currentScore > $maxScore) $maxScore = $currentScore;
            }
        }
        return $maxScore;
    }
    
    /**
     * Valida si una cadena tiene el formato de una CURP mexicana.
     *
     * @param string $curp La cadena a validar.
     * @return bool Devuelve true si el formato es válido, de lo contrario false.
     */
    public static function validar_curp(string $curp): bool
    {
        $regex = '/^[A-Z]{4}\d{6}[HMX][A-Z]{2}[A-Z0-9]{3}[0-9A-Z]\d$/';
        return preg_match($regex, $curp) === 1;
    }

    /**
     * Valida si una cadena tiene el formato de un RFC mexicano (persona física o moral).
     *
     * @param string $rfc La cadena a validar.
     * @return bool Devuelve true si el formato es válido, de lo contrario false.
     */
    public static function validar_rfc(string $rfc): bool
    {
        $regex = '/^[A-ZÑ&]{3,4}\d{6}(?:[A-Z\d]{3})?$/';
        return preg_match($regex, strtoupper($rfc)) === 1;
    }

    /**
     * Separa una cadena de texto que contiene múltiples nombres en un array.
     * Maneja separadores comunes como comas y la palabra 'VS'.
     *
     * @param string|null $texto La cadena con los nombres de las partes.
     * @return array Un array con los nombres individuales.
     */
    public static function split_partes(?string $texto): array
    {
        if (empty($texto)) {
            return [];
        }

        // Reemplazar 'VS' y 'VS.' (común en expedientes) por una coma para unificar el separador.
        $textoNormalizado = preg_replace('/\s+VS\.?\s+/i', ',', $texto);
        
        // Dividir la cadena por comas.
        $partes = explode(',', $textoNormalizado);

        // Limpiar cada nombre (quitar espacios en blanco) y filtrar elementos vacíos.
        $partesLimpias = array_filter(array_map('trim', $partes));

        // Devolver el resultado final, re-indexando el array para evitar huecos.
        return array_values($partesLimpias);
    }

    /**
     * Extrae la clave de la entidad federativa (estado) de una CURP.
     * La entidad federativa se encuentra en los caracteres 11 y 12.
     *
     * @param string $curp La CURP de 18 caracteres.
     * @return string La clave del estado (ej. "TM" para Tamaulipas) o una cadena vacía si la CURP no es válida.
     */
    public static function extract_estado(string $curp): string
    {
        if (strlen($curp) !== 18) {
            return '';
        }
        
        // Extrae los dos caracteres correspondientes al estado.
        return strtoupper(substr($curp, 11, 2));
    }
}
