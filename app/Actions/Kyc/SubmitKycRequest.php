<?php

declare(strict_types=1);

namespace App\Actions\Kyc;

use App\Enums\KycStatusEnum;
use App\Models\KycRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitKycRequest
{
    public function execute(User $user, array $data): KycRequest
    {
        return DB::transaction(function () use ($user, $data) {
            $existing = KycRequest::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->isFinal() === false) {
                throw ValidationException::withMessages([
                    'kyc' => 'Une demande KYC est déjà en cours.',
                ]);
            }

            if ($existing && $existing->isApproved()) {
                throw ValidationException::withMessages([
                    'kyc' => 'Votre KYC est déjà approuvé.',
                ]);
            }

            if ($existing) {
                $existing->delete();
            }

            return KycRequest::query()->create([
                'user_id'            => $user->id,
                'status'             => KycStatusEnum::PENDING,
                'id_photo_path'      => $data['id_photo_path'],
                'parcel_photo_path'  => $data['parcel_photo_path'],
                'admin_notes'        => null,
                'rejection_reason'   => null,
                'submitted_at'       => now(),
            ]);
        });
    }
}
