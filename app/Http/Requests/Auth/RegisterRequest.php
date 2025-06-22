<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ou false si uniquement via middleware invité
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name'  => ['required', 'string', 'max:50'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'role'       => ['required', new Enum(UserRoleEnum::class)],
            'phone'      => ['nullable', 'string', 'max:20'],
            'country'    => ['nullable', 'string', 'max:50'],
        ];
    }
}
