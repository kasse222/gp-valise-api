<?php

declare(strict_types=1);

namespace App\Mail\Kyc;

use App\Models\KycRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KycSubmittedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public function __construct(public readonly KycRequest $kycRequest) {}
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre dossier KYC a été reçu — Safe Move');
    }
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.kyc.submitted',
            with: [
                'kycRequest'   => $this->kycRequest,
                'user'         => $this->kycRequest->user,
                'dashboardUrl' => config('app.frontend_url') . '/traveler/profile',
            ],
        );
    }
}
