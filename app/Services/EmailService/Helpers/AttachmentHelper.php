<?php

// TODO: revisar si esto será necesario en el futuro

namespace App\Services\EmailService\Helpers;

use Illuminate\Support\Facades\Log;

class AttachmentHelper
{
    /**
     * Los archivos se limpian automáticamente al salir
     */
    public static function withTemp(callable $callback)
    {
        $temp = new TempAttachment();
        
        try {
            return $callback($temp);
        } catch (\Throwable $e) {
            Log::error('[AttachmentHelper] Error en context manager: ' . $e->getMessage());
            throw $e;
        } finally {
            // Garantizar limpieza incluso si hay excepción
            unset($temp);
        }
    }
}