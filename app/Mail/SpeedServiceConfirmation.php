<?php

namespace App\Mail;

use App\Models\Service\SendEmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class SpeedServiceConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $estimatedDate;

    public function __construct(
        public SendEmailLog $log
    ) {
        $esign = $this->log->subject;
        $signedAt = null;
        if ($esign && isset($esign->signed_at)) {
            $signedAt = $esign->signed_at;
        } else {
            $data = json_decode($this->log->data, true);
            $signedAt = isset($data['signed_at']) ? Carbon::parse($data['signed_at']) : now();
        }
        $this->estimatedDate = Carbon::parse($signedAt)->addDays(14);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->log->subject_line ?? '🎉 Tanda Tangan Diterima — Speed Service Kamu Resmi Aktif!',
        );
    }

    public function content(): Content
    {
        $data = json_decode($this->log->data, true);
        $clientName = $data['client_name'] ?? 'Klien';
        
        $esign = $this->log->subject;
        $signedAt = null;
        if ($esign && isset($esign->signed_at)) {
            $signedAt = $esign->signed_at;
        } else {
            $signedAt = isset($data['signed_at']) ? Carbon::parse($data['signed_at']) : now();
        }

        $signedAtFormatted = Carbon::parse($signedAt)->locale('id')->translatedFormat('d F Y');
        $estimatedDateFormatted = Carbon::parse($this->estimatedDate)->locale('id')->translatedFormat('d F Y');

        $waRaw = config('exata.admin_wa_number', '6281199896308');
        // Format as +62-811-9989-6308
        if (strlen($waRaw) === 13 && str_starts_with($waRaw, '62')) {
            $formattedWa = '+' . substr($waRaw, 0, 2) . '-' . substr($waRaw, 2, 3) . '-' . substr($waRaw, 5, 4) . '-' . substr($waRaw, 9);
        } else {
            $formattedWa = '+' . $waRaw;
        }

        return new Content(
            view: 'emails.speed_service_confirmation',
            with: [
                'clientName' => $clientName,
                'signedAtFormatted' => $signedAtFormatted,
                'estimatedDateFormatted' => $estimatedDateFormatted,
                'dashboardUrl' => config('app.url') . '/dashboard',
                'whatsappUrl' => 'https://wa.me/' . $waRaw,
                'formattedWa' => $formattedWa,
            ],
        );
    }

    public function attachments(): array
    {
        $esign = $this->log->subject;
        if ($esign && $esign->path) {
            try {
                if (\Illuminate\Support\Facades\Storage::disk('local')->exists($esign->path)) {
                    $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($esign->path);
                    return [
                        \Illuminate\Mail\Mailables\Attachment::fromPath($fullPath)
                            ->as('Dokumen_Perjanjian_Exata_Indonesia.pdf')
                            ->withMime('application/pdf'),
                    ];
                } else {
                    logger()->error("Attachment file not found on local disk", ['path' => $esign->path]);
                    // Flag the missing file in SendEmailLog
                    $this->log->update([
                        'error_message' => trim(($this->log->error_message ?? '') . ' | Attachment file not found: ' . $esign->path, ' | ')
                    ]);
                }
            } catch (\Throwable $e) {
                logger()->error("Failed to attach file to email", [
                    'exception' => $e->getMessage(),
                    'path' => $esign->path,
                ]);
                $this->log->update([
                    'error_message' => trim(($this->log->error_message ?? '') . ' | Failed to attach: ' . $e->getMessage(), ' | ')
                ]);
            }
        }
        return [];
    }
}
