<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\TripStatusEnum;
use App\Enums\TripTypeEnum;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'departure'      => ['required', 'string', 'max:255'],
            'destination'    => ['required', 'string', 'max:255'],
            'date' => [
                Rule::requiredIf(
                    fn() => $this->input('type_trip') !== TripTypeEnum::SUR_DEVIS->value
                ),
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'flight_number'  => ['nullable', 'string', 'max:255'],
            'capacity'       => ['required', 'integer', 'min:1'],
            'price_per_kg'   => ['required', 'numeric', 'min:0'],
            'status'         => ['nullable', new Enum(TripStatusEnum::class)],
            'type_trip'      => ['required', new Enum(TripTypeEnum::class)],

            'pickup_address'               => 'nullable|string|max:255',
            'pickup_city'                  => 'nullable|string|max:100',
            'pickup_latitude'              => 'nullable|numeric',
            'pickup_longitude'             => 'nullable|numeric',
            'pickup_approx_latitude'       => 'nullable|numeric',
            'pickup_approx_longitude'      => 'nullable|numeric',
            'pickup_instructions'          => 'nullable|string|max:500',
        ];
    }
}
