<?php

namespace App\Http\Requests\BookingStatusHistory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\BookingStatusEnum;
use Illuminate\Support\Facades\Auth;

class UpdateBookingStatusHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'old_status'  => ['sometimes', Rule::in(BookingStatusEnum::values())],
            'new_status'  => ['sometimes', Rule::in(BookingStatusEnum::values())],
            'changed_by'  => ['sometimes', 'exists:users,id'],
            'reason'      => ['nullable', 'string', 'max:1000'],
        ];
    }
}
