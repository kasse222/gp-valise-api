<?php

namespace App\Http\Requests\Luggage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\LuggageStatusEnum;
use Illuminate\Support\Facades\Auth;

class UpdateLuggageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // 🔐 ou à renforcer avec LuggagePolicy
    }

    public function rules(): array
    {
        return [
            'description'         => ['sometimes', 'string', 'max:1000'],
            'weight_kg'           => ['sometimes', 'numeric', 'min:0.1', 'max:100'],
            'length_cm'           => ['sometimes', 'numeric', 'min:1', 'max:200'],
            'width_cm'            => ['sometimes', 'numeric', 'min:1', 'max:200'],
            'height_cm'           => ['sometimes', 'numeric', 'min:1', 'max:200'],
            'pickup_city'         => ['sometimes', 'string', 'max:100'],
            'delivery_city'       => ['sometimes', 'string', 'max:100'],
            'pickup_date'         => ['sometimes', 'date', 'after_or_equal:today'],
            'delivery_date'       => ['sometimes', 'date', 'after:pickup_date'],
            'status'              => ['sometimes', Rule::in(LuggageStatusEnum::values())],
            'is_fragile'          => ['sometimes', 'boolean'],
            'insurance_requested' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_date.after_or_equal' => 'La date d’enlèvement ne peut pas être dans le passé.',
            'delivery_date.after'        => 'La date de livraison doit être après celle d’enlèvement.',
            'status.in'                  => 'Le statut fourni est invalide.',
        ];
    }
}
