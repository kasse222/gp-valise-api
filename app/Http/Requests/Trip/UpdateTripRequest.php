<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('trip');
        return $trip && auth()->check() && $trip->user_id === auth()->id();
    }

    public function rules(): array
    {
        return [
            'departure'      => ['sometimes', 'string', 'max:255'],
            'destination'    => ['sometimes', 'string', 'max:255'],
            'date'           => ['sometimes', 'date', 'after_or_equal:today'],
            'capacity'       => ['sometimes', 'numeric', 'min:0.1'],
            'status'         => ['sometimes', 'string'],
            'type_trip'      => ['sometimes', 'string'],
            'flight_number'  => ['nullable', 'string', 'max:100'],
        ];
    }
}
