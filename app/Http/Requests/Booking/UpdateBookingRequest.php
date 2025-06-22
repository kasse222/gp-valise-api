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

        // L’utilisateur doit être propriétaire OU voyageur du trip associé
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
                Rule::in(BookingStatusEnum::values()),
            ],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $booking = $this->route('booking');
            $newStatus = BookingStatusEnum::tryFrom($this->input('status'));

            if ($booking && $newStatus && ! $booking->canTransitionTo($newStatus)) {
                $validator->errors()->add('status', 'Changement de statut non autorisé.');
            }
        });
    }
}
