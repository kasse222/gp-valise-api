<?php

namespace App\Http\Requests\BookingItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); // on peut renforcer avec une Policy plus tard
    }

    public function rules(): array
    {
        return [
            'booking_id'  => ['required', 'exists:bookings,id'],
            'luggage_id'  => ['required', 'exists:luggages,id'],
            'trip_id'     => ['required', 'exists:trips,id'],
            'kg_reserved' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'price'       => ['required', 'numeric', 'min:0'],
        ];
    }
}
