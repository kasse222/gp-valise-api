<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'L’adresse email est requise.',
            'email.email'       => 'L’adresse email est invalide.',
            'password.required' => 'Le mot de passe est requis.',
        ];
    }
}
