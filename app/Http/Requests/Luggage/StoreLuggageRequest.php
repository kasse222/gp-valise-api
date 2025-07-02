<?php

namespace App\Http\Requests\Luggage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\LuggageStatusEnum;
use App\Models\Luggage;
use Illuminate\Support\Facades\Auth;

class StoreLuggageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trip_id'             => ['required', 'exists:trips,id'],
            'description'         => ['nullable', 'string', 'max:1000'],
            'weight_kg'           => ['required', 'numeric', 'min:0.1', 'max:100'],
            'length_cm'           => ['required', 'numeric', 'min:1', 'max:200'],
            'width_cm'            => ['required', 'numeric', 'min:1', 'max:200'],
            'height_cm'           => ['required', 'numeric', 'min:1', 'max:200'],
            'pickup_city'         => ['required', 'string', 'max:100'],
            'delivery_city'       => ['required', 'string', 'max:100'],
            'pickup_date'         => ['required', 'date', 'after_or_equal:today'],
            'delivery_date'       => ['required', 'date', 'after:pickup_date'],
            'status'              => ['nullable', Rule::in(LuggageStatusEnum::values())],
            'is_fragile'          => ['nullable', 'boolean'],
            'insurance_requested' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'weight_kg.required'     => 'Le poids est obligatoire.',
            'length_cm.required'     => 'La longueur est obligatoire.',
            'width_cm.required'      => 'La largeur est obligatoire.',
            'height_cm.required'     => 'La hauteur est obligatoire.',
            'pickup_city.required'   => 'La ville de départ est requise.',
            'delivery_city.required' => 'La ville de destination est requise.',
            'pickup_date.after_or_equal' => 'La date d’enlèvement doit être aujourd’hui ou plus tard.',
            'delivery_date.after'    => 'La date de livraison doit être après celle d’enlèvement.',
        ];
    }
}
