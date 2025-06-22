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
            'trip_id' => ['required', 'exists:trips,id'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
