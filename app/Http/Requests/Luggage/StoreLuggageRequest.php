<?php

namespace App\Http\Requests\Luggage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\LuggageStatusEnum;

class StoreLuggageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Uniquement l’utilisateur connecté (expéditeur)
        return auth()->check() && auth()->user()->isExpeditor();
    }

    public function rules(): array
    {
        return [
            'description'     => ['required', 'string', 'max:1000'],
            'weight_kg'       => ['required', 'numeric', 'min:0.1', 'max:100'],
            'length_cm'       => ['nullable', 'numeric', 'min:0'],
            'width_cm'        => ['nullable', 'numeric', 'min:0'],
            'height_cm'       => ['nullable', 'numeric', 'min:0'],
            'pickup_city'     => ['required', 'string', 'max:255'],
            'delivery_city'   => ['required', 'string', 'max:255'],
            'pickup_date'     => ['required', 'date', 'after_or_equal:today'],
            'delivery_date'   => ['required', 'date', 'after_or_equal:pickup_date'],
        ];
    }
}
