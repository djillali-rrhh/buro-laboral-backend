<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rolAdminId = DB::table('roles')->where('nombre', 'Administrador')->value('id');

        if (!$rolAdminId) {
            $this->command->error('El rol "Administrador" no fue encontrado. Ejecuta el RoleSeeder primero.');
            return;
        }

        $usuarioExists = DB::table('usuarios')->where([
            ['username', '=', 'administrador'],
            ['id_empresa', '=', 1],
        ])->exists();

        if ($usuarioExists) {
            $this->command->info('El usuario administrador para la empresa 1 ya existe.');
            return;
        }

        DB::table('usuarios')->insert([
            'username'          => 'administrador',
            'password'         => bcrypt('password'),
            'nombre'           => 'Admin',
            'apellido_paterno' => 'del',
            'apellido_materno' => 'Sistema',
            'email'            => 'admin@burolaboral.com',
            'telefono'         => '0000000000',
            'id_rol'           => $rolAdminId,
            'id_empresa'       => 1,
            'estatus'          => 1,
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now(),
        ]);
    }
}