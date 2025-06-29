<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\UserRoleEnum;
use Illuminate\Support\Facades\Auth;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // ou par Policy (user owner || admin)
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name'  => ['sometimes', 'string', 'max:255'],
            'email'      => ['sometimes', 'email', 'unique:users,email,' . $this->user?->id],
            'phone'      => ['sometimes', 'string', 'max:20'],
            'country'    => ['sometimes', 'string', 'max:255'],
            'role'       => ['sometimes', new Enum(UserRoleEnum::class)],
            'plan_id'    => ['nullable', 'exists:plans,id'],
        ];
    }
}
