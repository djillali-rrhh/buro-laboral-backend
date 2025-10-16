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
        Schema::create('registros_apimarket_api', function (Blueprint $table) {
            $table->id();
            $table->string('servicio', 50);
            $table->string('curp', 18)->nullable();
            $table->string('nss', 15)->nullable();
            $table->text('payload_request');
            $table->text('payload_response');
            $table->string('estatus', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_apimarket_api');
    }
};
