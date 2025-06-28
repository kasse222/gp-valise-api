<?php

namespace App\Http\Requests\BookingStatusHistory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\BookingStatusEnum;

class UpdateBookingStatusHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isModerator(); // sécurité renforcée
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

    public function messages(): array
    {
        return [
            'old_status.in'     => 'Le statut précédent est invalide.',
            'new_status.in'     => 'Le nouveau statut est invalide.',
            'changed_by.exists' => 'L’utilisateur modificateur n’existe pas.',
            'reason.max'        => 'La raison ne peut pas dépasser 1000 caractères.',
        ];
    }
}
