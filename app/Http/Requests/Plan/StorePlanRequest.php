<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PlanTypeEnum;
use Illuminate\Support\Facades\Auth;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // à restreindre si besoin
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:100'],
            'price'               => ['required', 'numeric', 'min:0'],
            'type'                => ['required', Rule::in(PlanTypeEnum::values())],
            'features'            => ['nullable', 'array'],
            'features.*'          => ['string'],
            'duration_days'       => ['required', 'integer', 'min:1'],
            'discount_percent'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_expires_at' => ['nullable', 'date', 'after:now'],
            'is_active'           => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'discount_expires_at.after' => 'La date d’expiration doit être dans le futur.',
        ];
    }
}
