<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class UpgradePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation se fait dans le contrôleur via ->authorize('update', $user)
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'Le plan est obligatoire.',
            'plan_id.integer'  => 'Le plan doit être un identifiant valide.',
            'plan_id.exists'   => 'Ce plan n’existe pas.',
        ];
    }
}
