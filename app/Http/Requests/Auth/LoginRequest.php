<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Toute requête de login est autorisée (publique).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation du formulaire de connexion.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ];
    }

    /**
     * Messages personnalisés pour l’API (facultatif mais pro UX).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'L’adresse e-mail est obligatoire.',
            'email.email'       => 'Le format de l’e-mail est invalide.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min'      => 'Le mot de passe doit contenir au moins :min caractères.',
        ];
    }
}
