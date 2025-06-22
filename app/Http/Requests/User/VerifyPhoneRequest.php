<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'phone_code' => ['required', 'string', 'size:6'], // Ex: SMS OTP à 6 chiffres
        ];
    }
}
