<?php

namespace App\Http\Requests\Booking;

use App\Enums\BookingStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        $user = $this->user();

        // ✅ Vérifie que l’utilisateur est propriétaire du booking OU du trip associé
        return $booking && $user && (
            $booking->user_id === $user->id ||
            $booking->trip?->user_id === $user->id
        );
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(BookingStatusEnum::values()), // ✅ Règle métier propre via Enum centralisée
            ],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $booking = $this->route('booking');
            $newStatus = BookingStatusEnum::tryFrom($this->input('status'));

            // ✅ Double sécurité métier via Enum
            if (! $booking || ! $newStatus) {
                return; // Cas anormal ou données manquantes
            }

            // ❌ Ne pas autoriser les transitions interdites
            if (! $booking->status->canTransitionTo($newStatus)) {
                $validator->errors()->add('status', 'Transition de statut non autorisée.');
            }

            // (optionnel) Empêcher de repasser sur le même statut
            if ($booking->status === $newStatus) {
                $validator->errors()->add('status', 'Le nouveau statut est identique à l’actuel.');
            }
        });
    }
}
