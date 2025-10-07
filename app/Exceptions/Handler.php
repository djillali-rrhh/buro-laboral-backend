<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {
            if ($exception instanceof NotFoundHttpException) {
                return $this->errorResponse(
                    'Ruta no encontrada: ' . $request->fullUrl(),
                    404
                );
            }

            if ($exception instanceof AuthenticationException) {
                return $this->errorResponse(
                    'No autenticado.',
                    401
                );
            }

            if ($exception instanceof AuthorizationException) {
                return $this->errorResponse(
                    'No tienes permiso para acceder a esta ruta.',
                    403
                );
            }

            if ($exception instanceof ValidationException) {
                return $this->errorResponse(
                    'Datos inválidos.',
                    422,
                    $exception->errors()
                );
            }

            return $this->errorResponse(
                'Error interno del servidor.',
                500
            );
        }

        return parent::render($request, $exception);
    }

    protected function invalidJson($request, ValidationException $exception)
    {
        return $this->errorResponse(
            'Datos inválidos.',
            $exception->status,
            $exception->errors()
        );
    }
}