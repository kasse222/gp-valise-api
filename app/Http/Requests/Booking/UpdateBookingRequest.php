<?php

namespace App\Http\Requests\Booking;

use App\Status\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'status' => ['required', Rule::in(array_column(BookingStatus::cases(), 'value'))],
        ];
    }
}
