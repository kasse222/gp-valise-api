<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = $this->user();
        return $user && $user->isExpeditor();
    }

    public function rules(): array
    {
        return [
            'trip_id'         => ['required', 'exists:trips,id'],
            'items'           => ['required', 'array', 'min:1'],
            'items.*.luggage_id'  => ['required', 'exists:luggages,id'],
            'items.*.kg_reserved' => ['required', 'numeric', 'min:0.1'],
            'items.*.price'       => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'trip_id.required'           => 'Le trajet est obligatoire.',
            'items.required'             => 'Vous devez fournir au moins une valise.',
            'items.*.luggage_id.required' => 'Chaque valise doit avoir un ID.',
            'items.*.kg_reserved.required' => 'Le poids réservé est obligatoire pour chaque valise.',
        ];
    }
}
