<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AcceptInvitationRequest extends FormRequest
{
    /**
     * 🔐 Seuls les invités non connectés peuvent accepter une invitation
     */
    public function authorize(): bool
    {
        return true; // 👤 Interdit aux utilisateurs connectés
    }

    /**
     * ✅ Validation des champs
     */
    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'uuid',
                Rule::exists('invitations', 'token')
                    ->whereNull('used_at')
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    }),
            ],
        ];
    }

    /**
     * 🧾 Messages d’erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Le lien d’invitation est manquant.',
            'token.uuid'     => 'Le format du lien d’invitation est invalide.',
            'token.exists'   => 'Ce lien est invalide, déjà utilisé ou expiré.',
        ];
    }
}
