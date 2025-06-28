<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && auth()->user::isExpeditor();
    }

    public function rules(): array
    {
        return [
            'trip_id'     => ['required', 'exists:trips,id'],
            'luggage_id'  => ['required', 'exists:luggages,id'],
            'kg_reserved' => ['required', 'numeric', 'min:0.1'],
            'price'       => ['nullable', 'numeric', 'min:0'], // prix facultatif si calcul auto
        ];
    }

    public function messages(): array
    {
        return [
            'trip_id.required'    => 'Le trajet est obligatoire.',
            'luggage_id.required' => 'Le bagage est requis.',
            'kg_reserved.required' => 'Le poids réservé doit être spécifié.',
        ];
    }
}
