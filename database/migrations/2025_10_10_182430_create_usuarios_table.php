<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50);
            $table->string('password');
            $table->string('nombre', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100);
            $table->string('email', 254);
            $table->string('telefono', 20);
            $table->foreignId('id_rol')->constrained('roles');
            $table->foreignId('id_empresa')->constrained('empresas');
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();

            $table->unique(['id_empresa', 'username']);
            $table->unique(['id_empresa', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};