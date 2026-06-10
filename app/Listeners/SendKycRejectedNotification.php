<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\KycRejected;
use App\Mail\Kyc\KycRejectedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendKycRejectedNotification implements ShouldQueue
{
    public function handle(KycRejected $event): void
    {
        $kycRequest = $event->kycRequest->loadMissing('user');
        Mail::to($kycRequest->user->email)
            ->queue(new KycRejectedMail($kycRequest));
    }
}
