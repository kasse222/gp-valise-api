<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AcceptInvitationRequest extends FormRequest
{
    /**
     * Autorise uniquement les utilisateurs non connectés
     */
    public function authorize(): bool
    {
        return !Auth::check(); // 👤 L’utilisateur ne doit pas être connecté
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'uuid',
                Rule::exists('invitations', 'token'), // ✅ vérifie que le token existe en BDD
            ],
        ];
    }

    /**
     * Messages d'erreurs personnalisés
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Le lien d’invitation est manquant.',
            'token.uuid'     => 'Le format du token est invalide.',
            'token.exists'   => 'Cette invitation est invalide ou a déjà été utilisée.',
        ];
    }
}
