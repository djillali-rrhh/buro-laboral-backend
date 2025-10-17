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
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->string('template_name')->nullable();
            $table->string('message_type');
            $table->json('variables')->nullable();
            $table->string('status');
            $table->json('response_json')->nullable();
            $table->enum('direction', ['outbound', 'inbound']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');
    }
};