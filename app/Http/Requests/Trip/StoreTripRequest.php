<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\TripStatusEnum;
use App\Enums\TripTypeEnum;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // Possibilité de restreindre au rôle VOYAGEUR plus tard
    }

    public function rules(): array
    {
        return [
            'departure'      => ['required', 'string', 'max:255'],
            'destination'    => ['required', 'string', 'max:255'],
            'date'           => ['required', 'date', 'after_or_equal:today'],
            'flight_number'  => ['nullable', 'string', 'max:255'],
            'capacity'       => ['required', 'integer', 'min:1'],
            'price_per_kg'   => ['required', 'numeric', 'min:0'],
            'status'         => ['nullable', new Enum(TripStatusEnum::class)], // facultatif à la création
            'type_trip'      => ['required', new Enum(TripTypeEnum::class)],
        ];
    }
}
