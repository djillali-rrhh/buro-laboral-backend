<?php

namespace App\Jobs;

use App\Services\EmailService\Strategies\SmtpEmailStrategy;
use App\Services\EmailService\Strategies\SendGridEmailStrategy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900];
    public $timeout = 120;

    protected array $emailData;
    protected string $strategy;

    public function __construct(array $emailData, string $strategy = 'smtp')
    {
        $this->emailData = $emailData;
        $this->strategy = $strategy;
        
        if (!empty($emailData['queue'])) {
            $this->onQueue($emailData['queue']);
            unset($this->emailData['queue']);
        }
        
        if (!empty($emailData['delay'])) {
            $this->delay($emailData['delay']);
            unset($this->emailData['delay']);
        }
    }

    public function handle(): void
    {
        Log::info("[SendEmailJob] Procesando email con estrategia: {$this->strategy}", [
            'attempt' => $this->attempts(),
            'to' => $this->emailData['to'] ?? 'unknown',
            'subject' => $this->emailData['subject'] ?? 'no subject'
        ]);

        // Instanciar la estrategia según el string
        $strategyInstance = match($this->strategy) {
            'sendgrid' => new SendGridEmailStrategy(),
            'smtp' => new SmtpEmailStrategy(),
            default => new SmtpEmailStrategy(),
        };

        $result = $strategyInstance->send($this->emailData);

        if (!$result) {
            Log::warning("[SendEmailJob] Email no pudo ser enviado", [
                'attempt' => $this->attempts(),
                'to' => $this->emailData['to'] ?? 'unknown'
            ]);

            if ($this->attempts() < $this->tries) {
                throw new \Exception("Failed to send email, will retry");
            }
        }

        Log::info("[SendEmailJob] Email enviado exitosamente", [
            'to' => $this->emailData['to'] ?? 'unknown',
            'attempt' => $this->attempts()
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[SendEmailJob] Email falló después de {$this->tries} intentos", [
            'strategy' => $this->strategy,
            'to' => $this->emailData['to'] ?? 'unknown',
            'subject' => $this->emailData['subject'] ?? 'no subject',
            'exception' => $exception->getMessage()
        ]);
    }
}