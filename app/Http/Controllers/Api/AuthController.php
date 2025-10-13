<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\Usuario;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Login: valida credenciales y devuelve un token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $usuario = Usuario::where('usuario', $data['usuario'])->first();

            if (!$usuario || !Hash::check($data['password'], $usuario->password) || $usuario->estatus != 1) {
                return $this->errorResponse('Credenciales incorrectas o usuario inactivo.', 401);
            }

            $usuario->tokens()->delete();

            $token = $usuario->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'usuario'        => $usuario->only([
                    'id', 'username', 'nombre', 'apellido_paterno', 'apellido_materno', 'email', 'id_rol'
                    ])
            ], 'Inicio de sesión exitoso.');

        } catch (\Throwable $e) {
            return $this->errorResponse(
                'Error inesperado en el servidor: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Logout: revoca el token actual del usuario autenticado.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Sesión cerrada correctamente.');
    }
}