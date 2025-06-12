<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 👉 On autorise l'accès à tous (route publique)
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'], // facultatif pour être plus souple
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'], // nécessite "password_confirmation"
            'role'       => ['required', 'in:voyageur,expediteur'], // 🌍 logique métier GP-Valise
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Le rôle doit être soit "voyageur", soit "expediteur".',
            'email.unique' => 'Cet email est déjà utilisé.',
        ];
    }
}
