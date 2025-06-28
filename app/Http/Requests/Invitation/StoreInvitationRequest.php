<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seuls les utilisateurs connectés peuvent inviter
        return auth::check();
    }

    public function rules(): array
    {
        return [
            'recipient_email' => [
                'required',
                'email',
                'max:255',
                // Empêche d’envoyer une invitation à soi-même
                Rule::notIn([Auth::user()->email]),
            ],
            'message' => [
                'nullable',
                'string',
                'max:1000',
            ],
            // Optionnel : expiration personnalisée
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_email.required' => 'L’adresse email du destinataire est obligatoire.',
            'recipient_email.email'    => 'Le format de l’email est invalide.',
            'recipient_email.not_in'   => 'Vous ne pouvez pas vous inviter vous-même.',
            'expires_at.after'         => 'La date d’expiration doit être dans le futur.',
        ];
    }
}
