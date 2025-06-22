<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Inscription publique
    }

    public function rules(): array
    {
        return [
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'email'           => ['required', 'email', 'unique:users,email'],
            'password'        => ['required', 'confirmed', Password::min(8)],
            'role'            => ['required', Rule::in(['expediteur', 'voyageur'])], // 🧠 Enum possible
            'phone'           => ['required', 'string', 'max:20'],
            'country'         => ['nullable', 'string', 'max:100'],
            'plan_id'         => ['nullable', 'exists:plans,id'], // facultatif à l'inscription
        ];
    }
}
