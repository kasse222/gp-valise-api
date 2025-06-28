<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Inscription publique
    }

    public function rules(): array
    {
        return [
            'first_name'  => ['required', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],

            'email'       => ['required', 'email', 'unique:users,email'],
            'password'    => ['required', 'confirmed', Password::min(8)],

            // ✅ Enum Laravel 9+ / 10+ : validation directe
            'role' => ['required', new Enum(UserRoleEnum::class)],

            'phone'       => ['required', 'string', 'max:20'],
            'country'     => ['nullable', 'string', 'max:100'],
            'plan_id'     => ['nullable', 'exists:plans,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required'  => 'Le nom est obligatoire.',
            'email.required'      => 'L’adresse email est requise.',
            'email.email'         => 'L’adresse email est invalide.',
            'email.unique'        => 'Cet email est déjà utilisé.',
            'password.required'   => 'Le mot de passe est requis.',
            'password.confirmed'  => 'La confirmation du mot de passe ne correspond pas.',
            'role.required'       => 'Le rôle est obligatoire.',
            'role.in'             => 'Le rôle fourni est invalide.',
            'phone.required'      => 'Le téléphone est requis.',
        ];
    }
}
