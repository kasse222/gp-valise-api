<?php

namespace App\Http\Requests\BookingItem;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); // à spécialiser plus tard
    }

    public function rules(): array
    {
        return [
            'booking_id'  => ['sometimes', 'exists:bookings,id'],
            'luggage_id'  => ['sometimes', 'exists:luggages,id'],
            'trip_id'     => ['sometimes', 'exists:trips,id'],
            'kg_reserved' => ['sometimes', 'numeric', 'min:0.1', 'max:100'],
            'price'       => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
