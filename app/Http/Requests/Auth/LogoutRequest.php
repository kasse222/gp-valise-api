<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LogoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        // On suppose que seul un utilisateur authentifié peut se déconnecter
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            // Tu peux ajouter un champ optionnel pour dire s’il faut révoquer tous les tokens
            'token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'logout_all.boolean' => 'Le champ logout_all doit être un booléen (true ou false).',
        ];
    }
}
