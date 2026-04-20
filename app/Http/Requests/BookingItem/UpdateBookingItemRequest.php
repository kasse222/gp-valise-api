<?php

namespace App\Http\Requests\BookingItem;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kg_reserved' => ['sometimes', 'numeric', 'min:0.1'],
            'price'       => ['sometimes', 'numeric', 'min:0'],

            'booking_id'  => ['prohibited'],
            'trip_id'     => ['prohibited'],
            'luggage_id'  => ['prohibited'],
        ];
    }
}
