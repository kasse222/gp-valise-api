<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRoleEnum;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{

    public function register(RegisterRequest $request)
    {
        try {
            $user = User::create([
                'first_name'     => $request->first_name,
                'last_name'      => $request->last_name,
                'email'          => $request->email,
                'password'       => Hash::make($request->password),
                'role'           => UserRoleEnum::from($request->role),
                'phone'          => $request->phone,
                'country'        => $request->country,
                'verified_user'  => false,
                'kyc_passed_at'  => null,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Inscription réussie.',
                'user'    => new UserResource($user),
                'token'   => $token,
            ], 201);
        } catch (Throwable $e) {
            Log::error('[AuthController@register] ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Une erreur est survenue lors de l’inscription.',
                'error'   => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }


    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont invalides.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'     => 'Connexion réussie.',
            'user'        => new UserResource($user),
            'token'       => $token,
            'token_type'  => 'Bearer',
            'is_admin'    => $user->isAdmin(),
            'is_premium'  => $user->isPremium(),
        ]);
    }


    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user'        => new UserResource($user),
            'is_admin'    => $user->isAdmin(),
            'is_premium'  => $user->isPremium(),
            'has_kyc'     => $user->hasKyc(),
            'role'        => $user->role->value,
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }


    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Toutes les sessions ont été fermées.',
        ]);
    }
}
