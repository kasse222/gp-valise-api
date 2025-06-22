<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\PlanTypeEnum;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin(); // à adapter selon rôles
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'price'               => ['required', 'numeric', 'min:0'],
            'type'                => ['required', new Enum(PlanTypeEnum::class)],
            'features'            => ['nullable', 'array'],
            'features.*'          => ['string', 'max:255'],
            'duration_days'       => ['required', 'integer', 'min:1'],
            'discount_percent'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_expires_at' => ['nullable', 'date', 'after:now'],
            'is_active'           => ['required', 'boolean'],
        ];
    }
}
