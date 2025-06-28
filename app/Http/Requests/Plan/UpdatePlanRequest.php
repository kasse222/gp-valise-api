<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PlanTypeEnum;
use Illuminate\Support\Facades\Auth;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // À restreindre via middleware ou policy si modification réservée à un admin
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'name'                => ['sometimes', 'string', 'max:100'],
            'price'               => ['sometimes', 'numeric', 'min:0'],
            'type'                => ['sometimes', Rule::in(PlanTypeEnum::values())],
            'features'            => ['sometimes', 'array'],
            'features.*'          => ['string'],
            'duration_days'       => ['sometimes', 'integer', 'min:1'],
            'discount_percent'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_expires_at' => ['nullable', 'date', 'after:now'],
            'is_active'           => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'discount_expires_at.after' => 'La date d’expiration doit être future.',
        ];
    }
}
