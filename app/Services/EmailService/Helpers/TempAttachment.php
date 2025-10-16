<?php

// TODO: revisar si esto será necesario en el futuro

namespace App\Services\EmailService\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TempAttachment
{
    private array $files = [];

    /**
     * Descargar desde URL
     */
    public function fromUrl(string $url, ?string $name = null): array
    {
        $response = Http::timeout(30)->get($url);
        
        if (!$response->successful()) {
            throw new \Exception("Error descargando desde URL: {$url} - Status: {$response->status()}");
        }

        $fileName = $name ?? $this->extractFileName($url);
        $tempPath = $this->getTempPath($fileName);

        file_put_contents($tempPath, $response->body());
        $this->files[] = $tempPath;

        return [
            'path' => $tempPath,
            'name' => $fileName,
            'mime' => $response->header('Content-Type') ?? $this->guessMimeType($fileName)
        ];
    }

    /**
     * Desde contenido base64
     */
    public function fromBase64(string $base64Content, string $name, ?string $mime = null): array
    {
        // Limpiar base64 si viene con data:image/png;base64,
        if (preg_match('/^data:([^;]+);base64,(.+)$/', $base64Content, $matches)) {
            $mime = $mime ?? $matches[1];
            $base64Content = $matches[2];
        }

        $content = base64_decode($base64Content);
        
        if ($content === false) {
            throw new \Exception("Contenido base64 inválido");
        }

        $tempPath = $this->getTempPath($name);
        file_put_contents($tempPath, $content);
        $this->files[] = $tempPath;

        return [
            'path' => $tempPath,
            'name' => $name,
            'mime' => $mime ?? $this->guessMimeType($name)
        ];
    }

    /**
     * Desde archivo subido (UploadedFile)
     */
    public function fromUploadedFile($file, ?string $name = null): array
    {
        $fileName = $name ?? $file->getClientOriginalName();
        $tempPath = $this->getTempPath($fileName);
        
        $file->move(dirname($tempPath), basename($tempPath));
        $this->files[] = $tempPath;

        return [
            'path' => $tempPath,
            'name' => $fileName,
            'mime' => $file->getMimeType()
        ];
    }

    /**
     * Desde Storage de Laravel
     */
    public function fromStorage(string $storagePath, ?string $name = null): array
    {
        if (!Storage::exists($storagePath)) {
            throw new \Exception("Archivo no encontrado en storage: {$storagePath}");
        }

        // Copiar a temp para que se limpie después
        $fileName = $name ?? basename($storagePath);
        $tempPath = $this->getTempPath($fileName);
        
        copy(Storage::path($storagePath), $tempPath);
        $this->files[] = $tempPath;

        return [
            'path' => $tempPath,
            'name' => $fileName,
            'mime' => Storage::mimeType($storagePath)
        ];
    }

    /**
     * Desde archivo local (NO se limpia automáticamente)
     */
    public function fromLocal(string $path, ?string $name = null): array
    {
        if (!file_exists($path)) {
            throw new \Exception("Archivo no encontrado: {$path}");
        }

        return [
            'path' => $path,
            'name' => $name ?? basename($path),
            'mime' => mime_content_type($path) ?: 'application/octet-stream'
        ];
    }

    /**
     * Obtener todos los attachments como array
     */
    public function toArray(): array
    {
        return array_map(function($path) {
            return [
                'path' => $path,
                'name' => basename($path),
                'mime' => mime_content_type($path) ?: 'application/octet-stream'
            ];
        }, $this->files);
    }

    /**
     * Generar path temporal único
     */
    private function getTempPath(string $fileName): string
    {
        $tempDir = storage_path('app/temp');
        
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        return $tempDir . '/' . Str::uuid() . '-' . $fileName;
    }

    /**
     * Extraer nombre de archivo desde URL
     */
    private function extractFileName(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $fileName = basename($path);
        
        // Si no tiene extensión, agregar .pdf por defecto
        if (!Str::contains($fileName, '.')) {
            $fileName .= '.pdf';
        }

        return $fileName;
    }

    /**
     * Adivinar MIME type desde extensión
     */
    private function guessMimeType(string $fileName): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * ✨ Auto-limpieza cuando se destruye el objeto
     */
    public function __destruct()
    {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }
}