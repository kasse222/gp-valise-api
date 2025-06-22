<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); // À sécuriser selon ton besoin (admin seulement ?)
    }

    public function rules(): array
    {
        return [
            'recipient_email' => ['sometimes', 'email', 'max:255'],
            'token'           => ['sometimes', 'string', 'max:255'],
            'used_at'         => ['nullable', 'date'],
        ];
    }
}
