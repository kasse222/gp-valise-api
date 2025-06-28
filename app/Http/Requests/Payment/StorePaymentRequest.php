<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\CurrencyEnum;
use Illuminate\Support\Facades\Auth;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // ou Policy si besoin
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'exists:bookings,id'],
            'amount'     => ['required', 'numeric', 'min:0.01'],
            'method'     => ['required', Rule::in(PaymentMethodEnum::values())],
            'status'     => ['required', Rule::in(PaymentStatusEnum::values())],
            'currency'   => ['required', Rule::in(CurrencyEnum::values())],
            'paid_at'    => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'La réservation liée est obligatoire.',
            'amount.required'     => 'Le montant est requis.',
            'amount.min'          => 'Le montant doit être supérieur à 0.',
            'method.in'           => 'Le mode de paiement est invalide.',
            'status.in'           => 'Le statut de paiement est invalide.',
            'currency.in'         => 'La devise est invalide.',
        ];
    }
}
