<?php

namespace App\Services\Nubarium;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\RegistrosNubariumApi;

class ValidationException extends \Exception {}

class NubariumService
{
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->username = env('NUBARIUM_USER');
        $this->password = env('NUBARIUM_PASS');

        if (empty($this->username) || empty($this->password)) {
            throw new \Exception("Nubarium credentials are not set in environment variables.");
        }

        Log::info('NubariumService initialized', [
            "username" => substr($this->username, 0, 4) . '...' . substr($this->username, -4),
        ]);
    }

    /**
     * Obtiene el cliente HTTP configurado con headers y basic auth
     */
    private function getHttpClient()
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->withBasicAuth($this->username, $this->password)
            ->timeout(30)
            ->connectTimeout(10);
    }

    public function obtenerCurpPorDatos(array $datos)
    {
        $response = $this->getHttpClient()->post('https://curp.nubarium.com/renapo/obtener_curp', [
            "nombre" => $datos['nombre'],
            "primerApellido" => $datos['primer_apellido'],
            "segundoApellido" => $datos['segundo_apellido'] ?? null,
            "fechaNacimiento" => $datos['fecha_nacimiento'],
            "entidad" => $datos['entidad'],
            "sexo" => $datos['sexo'],
        ]);

        $result = $response->successful() ? $response->json() : null;
        $estatus = ($result && $result['estatus'] === 'OK') ? 'OK' : 'ERROR';

        // Guardar registro y loguear siempre
        RegistrosNubariumApi::create([
            'payload_request' => $datos,
            'payload_response' => $result,
            'estatus' => $estatus,
        ]);

        Log::channel('nubarium')->info('Obtención de CURP', [
            'request' => $datos,
            'response' => $response->body(),
            'estatus' => $estatus,
        ]);

        return [
            'message' => $result['mensaje'] ?? ($estatus === 'OK' ? 'CURP obtenida exitosamente' : 'Error al obtener CURP'),
            'data' => $result,
        ];
    }

    public function validarCurp(string $curp)
    {
        if (empty($curp) || strlen($curp) !== 18) {
            throw new ValidationException("CURP inválida: debe tener 18 caracteres.");
        }

        $response = $this->getHttpClient()->post('https://curp.nubarium.com/renapo/v3/valida_curp', [
            'curp' => $curp,
        ]);

        $result = $response->successful() ? $response->json() : null;
        $estatus = ($result && $result['estatus'] === 'OK') ? 'OK' : 'ERROR';

        RegistrosNubariumApi::create([
            'payload_request' => ['curp' => $curp],
            'payload_response' => $result,
            'estatus' => $estatus,
        ]);

        Log::channel('nubarium')->info('Validación de CURP', [
            'request' => ['curp' => $curp],
            'response' => $response->body(),
            'estatus' => $estatus,
        ]);

        return [
            'message' => $result['mensaje'] ?? ($estatus === 'OK' ? 'CURP válida' : 'CURP inválida'),
            'data' => $result,
        ];
    }
}
