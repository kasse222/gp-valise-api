<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\VerifyPhoneRequest;
use App\Http\Requests\User\VerifyEmailRequest;
use App\Http\Requests\User\UpgradePlanRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * 🔍 Voir un utilisateur (admin ou soi-même)
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * ✏️ Modifier ses infos personnelles
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $user->update($request->validated());

        return new UserResource($user);
    }

    /**
     * 🔒 Changer son mot de passe
     */
    public function changePassword(ChangePasswordRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    /**
     * 📱 Vérifier le téléphone (manuellement)
     */
    public function verifyPhone(VerifyPhoneRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $user->update([
            'phone_verified_at' => now(),
        ]);

        return response()->json(['message' => 'Téléphone vérifié.']);
    }

    /**
     * 📧 Vérifier l'email (manuellement)
     */
    public function verifyEmail(VerifyEmailRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $user->update([
            'email_verified_at' => now(),
        ]);

        return response()->json(['message' => 'Email vérifié.']);
    }

    /**
     * 🚀 Changer de plan (abonnement)
     */
    public function upgradePlan(UpgradePlanRequest $request, User $user, PlanService $service)
    {
        $this->authorize('update', $user);

        $service->upgrade($user, $request->validated('plan_id'));

        return response()->json(['message' => 'Abonnement mis à jour.']);
    }
}
