<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateDocFile extends Command
{    protected $signature = 'docs:create {filename}';

    protected $description = 'Crea un nuevo archivo de documentación en el directorio docs/api';

    public function handle()
    {
        $filename = $this->argument('filename');
        $path = base_path("docs/api/{$filename}");

        File::ensureDirectoryExists(dirname($path));

        if (!File::exists($path)) {
            File::put($path, '# Título del Documento');
            $this->info("¡Archivo creado en: {$path}!");
        } else {
            $this->error("El archivo ya existe en: {$path}");
        }

        return 0;
    }
}