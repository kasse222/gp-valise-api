<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\CurrencyEnum;
use Illuminate\Support\Facades\Auth;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // à compléter avec une policy si besoin
    }

    public function rules(): array
    {
        return [
            'amount'     => ['sometimes', 'numeric', 'min:0.01'],
            'method'     => ['sometimes', Rule::in(PaymentMethodEnum::values())],
            'status'     => ['sometimes', Rule::in(PaymentStatusEnum::values())],
            'currency'   => ['sometimes', Rule::in(CurrencyEnum::values())],
            'paid_at'    => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min'      => 'Le montant doit être supérieur à 0.',
            'method.in'       => 'Le mode de paiement est invalide.',
            'status.in'       => 'Le statut de paiement est invalide.',
            'currency.in'     => 'La devise est invalide.',
        ];
    }
}
