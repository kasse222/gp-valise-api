<?php

declare(strict_types=1);

namespace App\Actions\Kyc;

use App\Enums\KycStatusEnum;
use App\Models\KycRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectKycRequest
{
    public function execute(KycRequest $kycRequest, User $admin, string $reason): KycRequest
    {
        return DB::transaction(function () use ($kycRequest, $admin, $reason) {
            $kycRequest = KycRequest::query()
                ->lockForUpdate()
                ->findOrFail($kycRequest->id);

            if (! $kycRequest->isPending()) {
                throw ValidationException::withMessages([
                    'kyc' => 'Cette demande KYC ne peut pas être rejetée depuis son statut actuel.',
                ]);
            }

            if (trim($reason) === '') {
                throw ValidationException::withMessages([
                    'reason' => 'La raison du rejet est obligatoire.',
                ]);
            }

            $kycRequest->update([
                'status'           => KycStatusEnum::REJECTED,
                'reviewed_by'      => $admin->id,
                'reviewed_at'      => now(),
                'rejection_reason' => $reason,
            ]);

            $kycRequest->user->update([
                'kyc_passed_at' => null,
            ]);

            return $kycRequest->fresh(['user', 'reviewer']);
        });
    }
}
