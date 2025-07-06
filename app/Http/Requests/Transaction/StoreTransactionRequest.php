<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use Illuminate\Support\Facades\Auth;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // Peut être renforcé via middleware ou policy
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', new Enum(CurrencyEnum::class)],
            'method' => ['required', new Enum(PaymentMethodEnum::class)],
            'booking_id' => [
                'required',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('user_id', Auth::id());
                }),
            ],
            // 'processed_at' et 'status' sont gérés en interne
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant est requis.',
            'amount.numeric' => 'Le montant doit être un nombre.',
            'amount.min' => 'Le montant doit être supérieur à 0.',
            'currency.required' => 'La devise est requise.',
            'currency.enum' => 'La devise sélectionnée est invalide.',
            'method.required' => 'La méthode de paiement est requise.',
            'method.enum' => 'La méthode de paiement est invalide.',
            'booking_id.required' => 'Un identifiant de réservation est requis.',
            'booking_id.exists' => 'Ce booking n’existe pas ou ne vous appartient pas.',
        ];
    }
}
