<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isTraveler();
    }

    public function rules(): array
    {
        return [
            'departure'      => ['required', 'string', 'max:255'],
            'destination'    => ['required', 'string', 'max:255'],
            'date'           => ['required', 'date', 'after_or_equal:today'],
            'capacity'       => ['required', 'numeric', 'min:0.1'],
            'status'         => ['nullable', 'string'], // facultatif à la création, parfois forcé via enum
            'type_trip'      => ['required', 'string'], // 📝 Enum TripTypeEnum à prévoir
            'flight_number'  => ['nullable', 'string', 'max:100'],
        ];
    }
}
