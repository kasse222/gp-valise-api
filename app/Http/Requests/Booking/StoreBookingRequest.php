<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seul un utilisateur connecté peut réserver
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'trip_id'    => ['required', 'exists:trips,id'],
            'items'      => ['required', 'array', 'min:1'],
            'items.*.luggage_id'  => ['required', 'exists:luggages,id'],
            'items.*.kg_reserved' => ['required', 'numeric', 'min:1'],
            'items.*.price'       => ['required', 'numeric', 'min:0'],
        ];
    }
}
