<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRoleEnum;
use App\Http\Resources\TravelerProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TravelerProfileController extends Controller
{
    public function show(User $user): JsonResponse
    {
        if ($user->role !== UserRoleEnum::TRAVELER) {
            return response()->json(['message' => 'Profil introuvable.', 'status' => 404], 404);
        }

        return (new TravelerProfileResource($user))->response();
    }
}
