<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UserRoleEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // à restreindre par Policy si besoin (admin only)
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'phone'      => ['required', 'string', 'max:20'],
            'country'    => ['required', 'string', 'max:255'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'role'       => ['required', new Enum(UserRoleEnum::class)],
            'plan_id'    => ['nullable', 'exists:plans,id'],
        ];
    }
}
