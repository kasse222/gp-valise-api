<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateInvitationRequest extends FormRequest
{

    public function authorize(): bool
    {
        $user = Auth::user();
        $invitation = $this->route('invitation');

        return $user && $invitation && (
            $user->is_admin || $user->id === $invitation->sender_id
        );
    }


    public function rules(): array
    {
        return [
            'recipient_email' => ['sometimes', 'email:rfc,dns', 'max:255'],
            'token'           => ['sometimes', 'uuid'],
            'used_at'         => ['nullable', 'date'],
        ];
    }


    public function messages(): array
    {
        return [
            'recipient_email.email' => 'L’adresse email du destinataire est invalide.',
            'token.uuid'            => 'Le format du token doit être un UUID valide.',
            'used_at.date'          => 'La date d’utilisation doit être une date valide.',
        ];
    }
}
