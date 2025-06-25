<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AcceptInvitationRequest extends FormRequest
{
    /**
     * Autorise uniquement un utilisateur invité (non connecté)
     */
    public function authorize(): bool
    {
        return !Auth::check(); // uniquement si l'utilisateur n'est pas encore connecté
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'uuid', 'exists:invitations,token'],
        ];
    }

    /**
     * Messages d'erreur personnalisés
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
