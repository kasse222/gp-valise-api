<?php

namespace App\Http\Requests\Luggage;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\LuggageStatusEnum;
use Illuminate\Validation\Rules\Enum;

class UpdateLuggageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && $this->luggage && $this->user()->id === $this->luggage->user_id;
    }

    public function rules(): array
    {
        return [
            'description'     => ['sometimes', 'string', 'max:1000'],
            'weight_kg'       => ['sometimes', 'numeric', 'min:0.1', 'max:100'],
            'length_cm'       => ['nullable', 'numeric', 'min:0'],
            'width_cm'        => ['nullable', 'numeric', 'min:0'],
            'height_cm'       => ['nullable', 'numeric', 'min:0'],
            'pickup_city'     => ['sometimes', 'string', 'max:255'],
            'delivery_city'   => ['sometimes', 'string', 'max:255'],
            'pickup_date'     => ['sometimes', 'date'],
            'delivery_date'   => ['sometimes', 'date', 'after_or_equal:pickup_date'],
            'status'          => ['sometimes', new Enum(LuggageStatusEnum::class)],
        ];
    }
}
