<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserRequest extends FormRequest
{
    /** @var \App\Models\User $user */
    public function authorize(): bool
    {
        return Auth::check() && Auth::id() === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'first_name'     => ['sometimes', 'string', 'max:100'],
            'last_name'      => ['sometimes', 'string', 'max:100'],
            'email'          => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
            'phone'          => ['sometimes', 'string', 'max:20'],
            'country'        => ['sometimes', 'string', 'max:100'],
            'role'           => ['prohibited'], // 🔒 Ne pas permettre la modification du rôle
            'verified_user'  => ['prohibited'],
            'plan_id'        => ['prohibited'], // Plan modifiable via logique métier séparée
            'kyc_passed_at'  => ['prohibited'],
        ];
    }
}
