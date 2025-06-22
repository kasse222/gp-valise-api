<?php

namespace App\Http\Requests\BookingStatusHistory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\BookingStatusEnum;

class StoreBookingStatusHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); // Policy BookingStatusHistory à ajouter plus tard si besoin
    }

    public function rules(): array
    {
        return [
            'booking_id'  => ['required', 'exists:bookings,id'],
            'old_status'  => ['required', Rule::in(BookingStatusEnum::values())],
            'new_status'  => ['required', Rule::in(BookingStatusEnum::values())],
            'changed_by'  => ['required', 'exists:users,id'],
            'reason'      => ['nullable', 'string', 'max:1000'],
        ];
    }
}
