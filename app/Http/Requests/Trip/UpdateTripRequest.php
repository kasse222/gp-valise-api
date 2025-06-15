<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'departure'      => ['sometimes', 'string', 'max:255'],
            'destination'    => ['sometimes', 'string', 'max:255'],
            'date'           => ['sometimes', 'date', 'after:now'],
            'capacity'       => ['sometimes', 'integer', 'min:1'],
            'flight_number'  => ['nullable', 'string', 'max:50'],
            'status'         => ['in:open,closed', 'nullable'],
        ];
    }
}
