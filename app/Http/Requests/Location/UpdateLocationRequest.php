<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // 🔐 Ou ajouter une Policy spécifique si besoin
    }

    public function rules(): array
    {
        return [
            'country'   => ['sometimes', 'string', 'max:100'],
            'city'      => ['sometimes', 'string', 'max:100'],
            'postcode'  => ['nullable', 'string', 'max:20'],
            'address'   => ['nullable', 'string', 'max:255'],
        ];
    }
}
