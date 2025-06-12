<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ✅ Accessible publiquement
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'Aucun utilisateur avec cet email.',
            'email.required' => 'L’email est requis.',
            'password.required' => 'Le mot de passe est requis.',
        ];
    }
}
