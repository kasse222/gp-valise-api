<?php

namespace App\Http\Requests\Booking;

use App\Enums\BookingStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum as EnumRule;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        $user = $this->user();

        return $booking && $user && (
            $booking->user_id === $user->id ||
            $booking->trip?->user_id === $user->id
        );
    }

    public function rules(): array
    {
        return [
            'status'  => ['required', new EnumRule(BookingStatusEnum::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $booking = $this->route('booking');
            $newStatus = BookingStatusEnum::tryFrom($this->input('status'));

            if (! $booking || ! $newStatus) return;

            if (! $booking->status->canTransitionTo($newStatus)) {
                $validator->errors()->add('status', 'Transition de statut non autorisée.');
            }

            if ($booking->status === $newStatus) {
                $validator->errors()->add('status', 'Le nouveau statut est identique à l’actuel.');
            }
        });
    }
}
