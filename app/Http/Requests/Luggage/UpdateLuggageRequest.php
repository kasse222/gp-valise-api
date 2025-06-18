<?php

namespace App\Http\Requests\Luggage;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLuggageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy `update` appliquée ailleurs
    }

    public function rules(): array
    {
        return [
            'description'     => ['sometimes', 'string', 'max:255'],
            'weight_kg'       => ['sometimes', 'numeric', 'min:0.1'],
            'dimensions'      => ['nullable', 'string', 'max:100'],
            'pickup_city'     => ['sometimes', 'string', 'max:100'],
            'delivery_city'   => ['sometimes', 'string', 'max:100'],
            'pickup_date'     => ['sometimes', 'date', 'after_or_equal:today'],
            'delivery_date'   => ['sometimes', 'date', 'after:pickup_date'],
        ];
    }
}
