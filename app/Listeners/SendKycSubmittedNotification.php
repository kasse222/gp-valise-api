<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\KycSubmitted;
use App\Mail\Kyc\KycSubmittedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendKycSubmittedNotification implements ShouldQueue
{
    public function handle(KycSubmitted $event): void
    {
        $kycRequest = $event->kycRequest->loadMissing('user');
        Mail::to($kycRequest->user->email)
            ->queue(new KycSubmittedMail($kycRequest));
    }
}
