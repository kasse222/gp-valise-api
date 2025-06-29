<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // utilisateur connecté uniquement
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'min:8'],
            'new_password'     => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
