<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Cliente',
            'Analista Laboral',
            'Administrador',
            'Superadministrador',
            'Facturador',
            'Superauditor',
        ];

        $data = array_map(function($role) {
            return [
                'nombre' => $role,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }, $roles);

        DB::table('roles')->insert($data);
    }
}