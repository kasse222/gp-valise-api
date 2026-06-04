<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRoleEnum;
use App\Models\KycRequest;
use App\Models\User;

class KycRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, KycRequest $kycRequest): bool
    {
        return $user->isAdmin()
            || $user->id === $kycRequest->user_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRoleEnum::SENDER,
            UserRoleEnum::TRAVELER,
        ], true);
    }

    public function update(User $user, KycRequest $kycRequest): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, KycRequest $kycRequest): bool
    {
        return $user->isAdmin();
    }

    public function approve(User $user, KycRequest $kycRequest): bool
    {
        return $user->isAdmin() && $kycRequest->isPending();
    }

    public function reject(User $user, KycRequest $kycRequest): bool
    {
        return $user->isAdmin() && $kycRequest->isPending();
    }
}
