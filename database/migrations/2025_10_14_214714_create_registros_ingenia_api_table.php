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
        Schema::create('registros_ingenia_api', function (Blueprint $table) {
            $table->id();
            $table->string('curp', 18)->nullable()->index();
            $table->string('tipo_consulta');
            $table->json('payload_request');
            $table->json('payload_response')->nullable();
            $table->string('estatus', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_ingenia_api');
    }
};