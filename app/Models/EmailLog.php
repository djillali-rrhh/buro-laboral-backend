<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo para el registro de envíos de correos electrónicos.
 *
 * Almacena información detallada de cada intento de envío incluyendo
 * destinatarios, estado, errores y metadata técnica.
 *
 * @package App\Models
 * 
 * @property int $id
 * @property string $from_email
 * @property string|null $from_name
 * @property array $to_recipients
 * @property array|null $cc_recipients
 * @property array|null $bcc_recipients
 * @property string $subject
 * @property string $strategy
 * @property string|null $template_type
 * @property string|null $template_name
 * @property string $status
 * @property int $attempts
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $failed_at
 * @property string|null $error_message
 * @property int|null $http_status_code
 * @property array|null $response_data
 * @property bool $has_attachments
 * @property int $attachments_count
 * @property int|null $content_size_bytes
 * @property float|null $send_duration_ms
 * @property string|null $context_type
 * @property int|null $context_id
 * @property int|null $user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class EmailLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'from_email',
        'from_name',
        'to_recipients',
        'cc_recipients',
        'bcc_recipients',
        'subject',
        'strategy',
        'template_type',
        'template_name',
        'status',
        'attempts',
        'sent_at',
        'failed_at',
        'error_message',
        'http_status_code',
        'response_data',
        'has_attachments',
        'attachments_count',
        'content_size_bytes',
        'send_duration_ms',
        'context_type',
        'context_id',
        'user_id',
    ];

    protected $casts = [
        'to_recipients' => 'array',
        'cc_recipients' => 'array',
        'bcc_recipients' => 'array',
        'response_data' => 'array',
        'has_attachments' => 'boolean',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'send_duration_ms' => 'decimal:2',
    ];

    /**
     * Scopes
     */

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByStrategy($query, string $strategy)
    {
        return $query->where('strategy', $strategy);
    }

    public function scopeToEmail($query, string $email)
    {
        return $query->whereJsonContains('to_recipients', ['email' => $email]);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Relaciones
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Métodos útiles
     */

    /**
     * Marca el email como enviado exitosamente.
     */
    public function markAsSent(?int $statusCode = null, ?array $responseData = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'http_status_code' => $statusCode,
            'response_data' => $responseData,
        ]);
    }

    /**
     * Marca el email como fallido.
     */
    public function markAsFailed(string $errorMessage, ?int $statusCode = null, ?array $responseData = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'http_status_code' => $statusCode,
            'response_data' => $responseData,
        ]);
    }

    /**
     * Incrementa el contador de intentos.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
        $this->update(['status' => 'retrying']);
    }

    /**
     * Obtiene los emails de los destinatarios principales.
     */
    public function getToEmailsAttribute(): array
    {
        return array_column($this->to_recipients ?? [], 'email');
    }

    /**
     * Verifica si el email fue enviado exitosamente.
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Verifica si el email falló.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Obtiene un resumen legible del log.
     */
    public function getSummary(): string
    {
        $to = implode(', ', $this->to_emails);
        $status = match($this->status) {
            'sent' => '✅ Enviado',
            'failed' => '❌ Fallido',
            'pending' => '⏳ Pendiente',
            'retrying' => '🔄 Reintentando',
        };

        return "{$status} | Para: {$to} | Asunto: {$this->subject}";
    }
}