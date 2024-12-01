<?php

namespace App\Jobs;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Resend\Laravel\Facades\Resend;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transfer;

    public function __construct(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    public function handle()
    {
        // Enviar email usando Resend
        Resend::emails()->send([
            'from' => 'notifications@yourdomain.com',
            'to' => $this->transfer->recipient_email,
            'subject' => 'Archivos compartidos contigo',
            'html' => view('emails.transfer', [
                'transfer' => $this->transfer
            ])->render()
        ]);
    }
}
