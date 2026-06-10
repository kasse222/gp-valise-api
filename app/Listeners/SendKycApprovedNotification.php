<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\KycApproved;
use App\Mail\Kyc\KycApprovedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendKycApprovedNotification implements ShouldQueue
{
    public function handle(KycApproved $event): void
    {
        $kycRequest = $event->kycRequest->loadMissing('user');
        Mail::to($kycRequest->user->email)
            ->queue(new KycApprovedMail($kycRequest));
    }
}
