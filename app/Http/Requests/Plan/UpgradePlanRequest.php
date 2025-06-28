<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PlanTypeEnum;
use Illuminate\Support\Facades\Auth;

class UpgradePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // Le user doit être connecté pour upgrader
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'exists:plans,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'Veuillez sélectionner un plan.',
            'plan_id.exists'   => 'Le plan sélectionné n’est pas valide.',
        ];
    }
}
