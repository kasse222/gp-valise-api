<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 🔐 Contrôle via Policy côté contrôleur
    }

    public function rules(): array
    {
        return [
            'country'   => ['required', 'string', 'max:100'],
            'city'      => ['required', 'string', 'max:100'],
            'postcode'  => ['nullable', 'string', 'max:20'],
            'address'   => ['nullable', 'string', 'max:255'],
            // latitude, longitude, type, position, trip_id, order_index → injectés côté backend
        ];
    }

    public function messages(): array
    {
        return [
            'country.required' => 'Le pays est requis.',
            'city.required'    => 'La ville est requise.',
        ];
    }
}
