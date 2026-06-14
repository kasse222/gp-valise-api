<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

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
            'trip_id'              => ['required', 'exists:trips,id'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.luggage_id'   => ['required', 'exists:luggages,id'],
            'items.*.kg_reserved'  => ['required', 'numeric', 'min:0.1'],
            'items.*.price'        => ['nullable', 'numeric', 'min:0'],

            // Destinataire — obligatoire Instant Booking
            'recipient_name'       => ['required', 'string', 'max:255'],
            'recipient_phone'      => ['required', 'string', 'max:30'],
            'recipient_email'      => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'trip_id.required'              => 'Le trajet est obligatoire.',
            'items.required'                => 'Vous devez fournir au moins une valise.',
            'items.*.luggage_id.required'   => 'Chaque valise doit avoir un ID.',
            'items.*.kg_reserved.required'  => 'Le poids réservé est obligatoire pour chaque valise.',
            'recipient_name.required'       => 'Le nom du destinataire est obligatoire.',
            'recipient_phone.required'      => 'Le téléphone du destinataire est obligatoire.',
            'recipient_email.required'      => 'L\'email du destinataire est obligatoire.',
            'recipient_email.email'         => 'L\'email du destinataire est invalide.',
        ];
    }
}
