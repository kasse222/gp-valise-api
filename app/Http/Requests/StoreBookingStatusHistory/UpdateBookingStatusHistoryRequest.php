<?php

namespace App\Http\Requests\BookingStatusHistory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\BookingStatusEnum;

class UpdateBookingStatusHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); // à spécialiser si nécessaire
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
