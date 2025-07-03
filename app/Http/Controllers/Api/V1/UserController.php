<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\User\ChangeUserPassword;
use App\Actions\User\VerifyUserEmail;
use Illuminate\Routing\Controller;
use App\Http\Requests\Plan\UpgradePlanRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\VerifyPhoneRequest;
use App\Http\Requests\User\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function show(User $user)
    {
        $this->authorize('view', $user);
        return new UserResource($user);
    }

    public function update(UpgradePlanRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $user->update($request->validated());
        return new UserResource($user);
    }

    public function changePassword(ChangePasswordRequest $request, User $user)
    {
        $this->authorize('update', $user);
        ChangeUserPassword::execute($user, $request->new_password);
        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    public function verifyPhone(VerifyPhoneRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $user->update(['phone_verified_at' => now()]);
        return response()->json(['message' => 'Téléphone vérifié.']);
    }

    public function verifyEmail(VerifyEmailRequest $request, User $user)
    {
        $this->authorize('update', $user);
        VerifyUserEmail::execute($user);

        return response()->json(['message' => 'Email vérifié.']);
    }

    public function upgradePlan(UpgradePlanRequest $request, User $user, PlanService $service)
    {
        $this->authorize('update', $user);
        $service->upgrade($user, $request->validated('plan_id'));
        return response()->json(['message' => 'Abonnement mis à jour.']);
    }
}
