<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seul un administrateur peut créer un nouvel utilisateur
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'first_name'       => ['required', 'string', 'max:100'],
            'last_name'        => ['required', 'string', 'max:100'],
            'email'            => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'         => ['required', 'string', Password::defaults()],
            'phone'            => ['nullable', 'string', 'max:20'],
            'country'          => ['nullable', 'string', 'max:100'],
            'role'             => ['required', 'in:admin,expeditor,traveler'], // adapte selon UserRoleEnum
            'verified_user'    => ['boolean'],
            'plan_id'          => ['nullable', 'exists:plans,id'],
            'plan_expires_at'  => ['nullable', 'date'],
            'kyc_passed_at'    => ['nullable', 'date'],
        ];
    }
}
