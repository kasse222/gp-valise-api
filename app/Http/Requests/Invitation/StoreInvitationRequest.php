<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    /**
     * 🔐 Seuls les utilisateurs connectés peuvent envoyer une invitation
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * ✅ Règles de validation des champs
     */
    public function rules(): array
    {
        $user = Auth::user();

        return [
            'recipient_email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::notIn([$user?->email]), // ⛔ Empêche de s’auto-inviter
                Rule::unique('invitations', 'recipient_email')
                    ->where('sender_id', $user?->id)
                    ->whereNull('used_at'), // ✅ Évite les doublons non encore utilisés
            ],
            'message' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    /**
     * 🧾 Messages personnalisés
     */
    public function messages(): array
    {
        return [
            'recipient_email.required'  => 'L’adresse email du destinataire est obligatoire.',
            'recipient_email.email'     => 'Le format de l’email est invalide.',
            'recipient_email.not_in'    => 'Vous ne pouvez pas vous inviter vous-même.',
            'recipient_email.unique'    => 'Vous avez déjà envoyé une invitation à cette adresse non encore utilisée.',
            'expires_at.date'           => 'La date d’expiration est invalide.',
            'expires_at.after'          => 'La date d’expiration doit être ultérieure à maintenant.',
        ];
    }
}
