<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'utilisateur doit être connecté, et de rôle "expéditeur"
        return auth()->check() && auth()->user()->isExpeditor();
    }

    public function rules(): array
    {
        return [
            'booking_id'  => ['required', 'exists:bookings,id'],
            'luggage_id'  => ['required', 'exists:luggages,id'],
            'trip_id'     => ['required', 'exists:trips,id'],
            'kg_reserved' => ['nullable', 'numeric', 'min:0'],
            'price'       => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
