<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seul le propriétaire (ou admin) pourra modifier
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:en_attente,accepte,refuse,annule,termine'],
        ];
    }
}
