<?php

namespace App\Http\Requests\BookingItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBookingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user && $user->isExpeditor();
    }

    public function rules(): array
    {
        return [
            'kg_reserved' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'price'       => ['nullable', 'numeric', 'min:0'], // nullable si prix auto
            'luggage_id'  => ['required', 'exists:luggages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'kg_reserved.min' => 'Le poids réservé doit être supérieur à 0.',
            'price.numeric'   => 'Le prix doit être un nombre.',
        ];
    }
}
