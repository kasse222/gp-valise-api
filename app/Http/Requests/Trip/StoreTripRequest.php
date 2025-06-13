<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

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
            'status'         => ['in:open,closed', 'nullable'], // si tu veux gérer les trajets archivé/actif
        ];
    }
}
