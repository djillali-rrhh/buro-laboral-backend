<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            
            // Información del email
            $table->string('from_email', 255);
            $table->string('from_name', 255)->nullable();
            $table->json('to_recipients');  // [{email, name}, ...]
            $table->json('cc_recipients')->nullable();
            $table->json('bcc_recipients')->nullable();
            $table->string('subject', 500);
            
            // Estrategia y plantilla
            $table->string('strategy', 50)->default('smtp');  // smtp, sendgrid, etc.
            $table->string('template_type', 50)->nullable();  // html, view, mailable
            $table->string('template_name', 255)->nullable();  // nombre de vista o mailable
            
            // Estado del envío
            $table->enum('status', ['pending', 'sent', 'failed', 'retrying'])->default('pending');
            $table->integer('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // Detalles técnicos
            $table->text('error_message')->nullable();
            $table->integer('http_status_code')->nullable();
            $table->json('response_data')->nullable();
            
            // Metadata adicional
            $table->boolean('has_attachments')->default(false);
            $table->integer('attachments_count')->default(0);
            $table->integer('content_size_bytes')->nullable();
            $table->decimal('send_duration_ms', 10, 2)->nullable();
                        
            // Auditoría
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('status');
            $table->index('strategy');
            $table->index('created_at');
            $table->index('sent_at');
            $table->index(['from_email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};