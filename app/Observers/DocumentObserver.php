<?php

namespace App\Observers;

use App\Models\Document;
use App\Models\Service\SendEmailLog;
use App\Repositories\Service\SendEmailLogRepository;
use App\Mail\DocumentAvailableNotification;
use App\Mail\EsignReminderNotification;
use App\Mail\NormalDisbursementConfirmation;
use App\Enums\Service\EmailLogStatus;
use App\Models\User;
use App\Models\Client\Client;

class DocumentObserver
{
    public function created(Document $document): void
    {
        $this->notifyClient($document);
    }

    public function updated(Document $document): void
    {
        if ($document->isDirty('bucket_path') || $document->isDirty('original_filename')) {
            $this->notifyClient($document);
        }
    }

    protected function notifyClient(Document $document): void
    {
        $client = $document->client;
        if (!$client) {
            return;
        }

        $user = User::where('client_id', $client->id)->first();
        if (!$user || !$user->email) {
            logger()->warning("Cannot notify client of document: client user or email not found", [
                'client_id' => $client->id,
            ]);
            return;
        }

        $claimType = $client->claim_type;

        if (in_array($claimType, [Client::CLAIM_TYPE_SPEED, Client::CLAIM_TYPE_ADJUSTMENT])) {
            $esign = $client->esign;
            $esignCompleted = $esign && $esign->signed_at !== null;

            if (!$esignCompleted) {
                $exists = SendEmailLog::where('subject_type', Document::class)
                    ->where('subject_id', $document->id)
                    ->where('mailable', EsignReminderNotification::class)
                    ->whereIn('status', [
                        EmailLogStatus::PENDING,
                        EmailLogStatus::SENDING,
                        EmailLogStatus::SENT
                    ])
                    ->exists();

                if ($exists) {
                    return;
                }

                SendEmailLogRepository::create([
                    'subject_type' => Document::class,
                    'subject_id' => $document->id,
                    'data' => json_encode([
                        'client_name' => $client->name,
                        'document_type' => $document->type,
                    ]),
                    'email' => $user->email,
                    'mailable' => EsignReminderNotification::class,
                    'subject_line' => "Tanda Tangan Dibutuhkan — Proses Nenkin Speed Service Kamu Menunggu",
                    'status' => EmailLogStatus::PENDING,
                ]);
            }
        } elseif ($claimType === Client::CLAIM_TYPE_NORMAL) {
            $exists = SendEmailLog::where('subject_type', Document::class)
                ->where('subject_id', $document->id)
                ->where('mailable', NormalDisbursementConfirmation::class)
                ->whereIn('status', [
                    EmailLogStatus::PENDING,
                    EmailLogStatus::SENDING,
                    EmailLogStatus::SENT
                ])
                ->exists();

            if ($exists) {
                return;
            }

            SendEmailLogRepository::create([
                'subject_type' => Document::class,
                'subject_id' => $document->id,
                'data' => json_encode([
                    'client_name' => $client->name,
                    'document_type' => $document->type,
                ]),
                'email' => $user->email,
                'mailable' => NormalDisbursementConfirmation::class,
                'subject_line' => "Konfirmasi Pencairan Nenkin — Proses Pencairan Kamu Sedang Berjalan",
                'status' => EmailLogStatus::PENDING,
            ]);
        } else {
            // Fallback to original DocumentAvailableNotification
            $exists = SendEmailLog::where('subject_type', Document::class)
                ->where('subject_id', $document->id)
                ->where('mailable', DocumentAvailableNotification::class)
                ->whereIn('status', [
                    EmailLogStatus::PENDING,
                    EmailLogStatus::SENDING,
                    EmailLogStatus::SENT
                ])
                ->exists();

            if ($exists) {
                return;
            }

            SendEmailLogRepository::create([
                'subject_type' => Document::class,
                'subject_id' => $document->id,
                'data' => json_encode([
                    'client_name' => $client->name,
                    'document_type' => $document->type,
                ]),
                'email' => $user->email,
                'mailable' => DocumentAvailableNotification::class,
                'subject_line' => " Selamat Nenkin 20% Kamu Sudah Cair!",
                'status' => EmailLogStatus::PENDING,
            ]);
        }
    }
}
