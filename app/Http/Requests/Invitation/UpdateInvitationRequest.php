<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $invitation = $this->route('invitation');

        return $user && $invitation && (
            $user->isAdmin() || $invitation->sender_id === $user->id
        );
    }

    public function rules(): array
    {
        return [
            'recipient_email' => ['sometimes', 'email', 'max:255'],
            'token'           => ['sometimes', 'string', 'max:255'],
            'used_at'         => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_email.email' => 'L’email fourni n’est pas valide.',
            'token.string'          => 'Le token doit être une chaîne de caractères.',
            'used_at.date'          => 'La date d’utilisation est invalide.',
        ];
    }
}
