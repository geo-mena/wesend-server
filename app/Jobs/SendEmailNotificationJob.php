<?php

namespace App\Jobs;

use App\Models\Transfer;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transfer;

    public function __construct(Transfer $transfer)
    {
        $this->transfer = $transfer;

        $this->transfer->expires_at = Carbon::parse($this->transfer->expires_at);
    }

    public function handle()
    {
        try {
            // Enviar email usando Resend
            Resend::emails()->send([
                'from' => 'noreply@signature-server.online',
                'to' => $this->transfer->recipient_email,
                'subject' => 'Archivos compartidos contigo',
                'html' => view('emails.transfer', [
                    'transfer' => $this->transfer
                ])->render()
            ]);
        } catch (Exception $e) {
            Log::error('Resend error: ' . $e->getMessage());
            throw $e;
        }
    }
}
