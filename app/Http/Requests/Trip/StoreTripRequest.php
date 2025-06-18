<?php

namespace App\Http\Requests\Trip;

use App\Status\TripTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ✔️ Accessible via auth:sanctum
    }

    public function rules(): array
    {
        return [
            'departure'      => ['required', 'string', 'max:255'],
            'destination'    => ['required', 'string', 'max:255'],
            'date'           => ['required', 'date', 'after:now'],
            'capacity'       => ['required', 'integer', 'min:1'],
            'flight_number'  => ['nullable', 'string', 'max:50'],
            'status'         => ['nullable', Rule::in(['actif', 'complet', 'annule'])],
            'type_trip'      => ['nullable', Rule::in(TripTypeEnum::values())],
        ];
    }
}
