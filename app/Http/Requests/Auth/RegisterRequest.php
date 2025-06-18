<?php

namespace App\Http\Requests\Auth;

use App\Status\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'country'    => ['nullable', 'string', 'max:100'],

            //  Ne permettre QUE les rôles "voyageur" ou "expediteur"
            'role'       => ['required', new Enum(UserRole::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Un rôle est requis pour l’inscription.',
        ];
    }

    public function validatedRole(): string
    {
        // Bloquer explicitement les rôles sensibles
        $role = $this->get('role');

        if (in_array($role, ['admin', 'premium'])) {
            abort(403, 'Ce rôle n’est pas autorisé à l’inscription.');
        }

        return $role;
    }
}
