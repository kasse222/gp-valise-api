<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 🔐 Autorisation contrôlée via policy
    }

    public function rules(): array
    {
        return [
            'country'   => ['required', 'string', 'max:100'],
            'city'      => ['required', 'string', 'max:100'],
            'postcode'  => ['nullable', 'string', 'max:20'],
            'address'   => ['nullable', 'string', 'max:255'],
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
