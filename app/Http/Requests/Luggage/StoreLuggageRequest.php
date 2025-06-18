<?php

namespace App\Http\Requests\Luggage;

use Illuminate\Foundation\Http\FormRequest;

class StoreLuggageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ✔️ L'utilisateur est déjà authentifié via Sanctum
    }

    public function rules(): array
    {
        return [
            'description'     => ['required', 'string', 'max:255'],
            'weight_kg'       => ['required', 'numeric', 'min:0.1'],
            'dimensions'      => ['nullable', 'string', 'max:100'],
            'pickup_city'     => ['required', 'string', 'max:100'],
            'delivery_city'   => ['required', 'string', 'max:100'],
            'pickup_date'     => ['required', 'date', 'after_or_equal:today'],
            'delivery_date'   => ['required', 'date', 'after:pickup_date'],
        ];
    }
}
