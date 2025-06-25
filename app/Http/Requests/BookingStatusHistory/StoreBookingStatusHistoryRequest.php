<?php

namespace App\Http\Requests\BookingStatusHistory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\BookingStatusEnum;

class StoreBookingStatusHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 🔐 Le contrôleur gère l'autorisation via la réservation
    }

    public function rules(): array
    {
        return [
            'old_status' => ['required', 'in:' . implode(',', BookingStatusEnum::values())],
            'new_status' => ['required', 'in:' . implode(',', BookingStatusEnum::values())],
            'reason'     => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'old_status.required' => 'L\'ancien statut est requis.',
            'new_status.required' => 'Le nouveau statut est requis.',
            'old_status.in'       => 'Ancien statut invalide.',
            'new_status.in'       => 'Nouveau statut invalide.',
        ];
    }
}
