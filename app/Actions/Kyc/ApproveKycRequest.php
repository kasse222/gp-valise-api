<?php

declare(strict_types=1);

namespace App\Actions\Kyc;

use App\Enums\KycStatusEnum;
use App\Models\KycRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveKycRequest
{
    public function execute(KycRequest $kycRequest, User $admin, ?string $notes = null): KycRequest
    {
        return DB::transaction(function () use ($kycRequest, $admin, $notes) {
            $kycRequest = KycRequest::query()
                ->lockForUpdate()
                ->findOrFail($kycRequest->id);

            if (! $kycRequest->isPending()) {
                throw ValidationException::withMessages([
                    'kyc' => 'Cette demande KYC ne peut pas être approuvée depuis son statut actuel.',
                ]);
            }

            $kycRequest->update([
                'status'      => KycStatusEnum::APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_notes' => $notes,
            ]);

            $kycRequest->user->update([
                'kyc_passed_at' => now(),
            ]);

            return $kycRequest->fresh(['user', 'reviewer']);
        });
    }
}
