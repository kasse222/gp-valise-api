<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\PlanTypeEnum;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'                => ['sometimes', 'string', 'max:255'],
            'price'               => ['sometimes', 'numeric', 'min:0'],
            'type'                => ['sometimes', new Enum(PlanTypeEnum::class)],
            'features'            => ['nullable', 'array'],
            'features.*'          => ['string', 'max:255'],
            'duration_days'       => ['sometimes', 'integer', 'min:1'],
            'discount_percent'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_expires_at' => ['nullable', 'date', 'after:now'],
            'is_active'           => ['sometimes', 'boolean'],
        ];
    }
}
