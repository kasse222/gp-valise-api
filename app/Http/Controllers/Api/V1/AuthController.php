<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRoleEnum;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    /**
     * Enregistrement d’un nouvel utilisateur
     */
    public function register(RegisterRequest $request)
    {
        $this->authorize('create', User::class); // seulement si c’est une route protégée

        try {
            $user = User::create([
                'first_name'      => $request->first_name,
                'last_name'       => $request->last_name,
                'email'           => $request->email,
                'password'        => Hash::make($request->password),
                'role'            => UserRoleEnum::from($request->role), // Enum 💡
                'phone'           => $request->phone,
                'country'         => $request->country,
                'verified_user'   => false,
                'kyc_passed_at'   => null,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Inscription réussie.',
                'user'    => new UserResource($user),
                'token'   => $token,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de l’inscription.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Connexion d’un utilisateur
     */
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
        ]);
    }

    /**
     * Infos de l’utilisateur connecté
     */
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Déconnexion de l’utilisateur
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }
}
