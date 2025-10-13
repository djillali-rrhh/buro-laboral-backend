<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresaExists = DB::table('empresas')->where('nombre', 'RRHH INGENIA SA DE CV')->exists();

        if (!$empresaExists) {
            DB::table('empresas')->insert([
                'nombre' => 'RRHH INGENIA SA DE CV',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}