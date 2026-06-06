<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\TripStatusEnum;
use App\Enums\TripTypeEnum;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'departure'      => ['sometimes', 'string', 'max:255'],
            'destination'    => ['sometimes', 'string', 'max:255'],
            'date'           => ['sometimes', 'date', 'after_or_equal:today'],
            'flight_number'  => ['nullable', 'string', 'max:255'],
            'capacity'       => ['sometimes', 'integer', 'min:1'],
            'price_per_kg'   => ['sometimes', 'numeric', 'min:0'],
            'status'         => ['sometimes', new Enum(TripStatusEnum::class)],
            'type_trip'      => ['sometimes', new Enum(TripTypeEnum::class)],

            // Pickup location — modifiable après création
            'pickup_address'               => ['nullable', 'string', 'max:255'],
            'pickup_city'                  => ['nullable', 'string', 'max:100'],
            'pickup_latitude'              => ['nullable', 'numeric'],
            'pickup_longitude'             => ['nullable', 'numeric'],
            'pickup_approx_latitude'       => ['nullable', 'numeric'],
            'pickup_approx_longitude'      => ['nullable', 'numeric'],
            'pickup_instructions'          => ['nullable', 'string', 'max:500'],
        ];
    }
}
