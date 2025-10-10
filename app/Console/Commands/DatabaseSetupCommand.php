<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;

class DatabaseSetupCommand extends Command
{
    /**
     * La firma del comando: corta y fácil de recordar.
     *
     * @var string
     */
    protected $signature = 'db:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea la base de datos de SQL Server (si no existe) y ejecuta las migraciones.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $config = config('database.connections.sqlsrv');
        $database = $config['database'];
        $host = $config['host'];
        $port = $config['port'];
        $username = $config['username'];
        $password = $config['password'];

        try {
            $pdo = new PDO("sqlsrv:server=$host,$port", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $query = $pdo->query("SELECT name FROM sys.databases WHERE name = N'$database'");

            if ($query->fetch()) {
                $this->info("La base de datos '$database' ya existe.");
            } else {
                $pdo->exec("CREATE DATABASE [$database]");
                $this->info("¡Base de datos '$database' creada exitosamente!");
            }

            $this->info("Ejecutando migraciones...");
            $pdo = null; 
            $this->call('migrate');

        } catch (\PDOException $e) {
            $this->error("No se pudo configurar la base de datos: " . $e->getMessage());
        }
    }
}