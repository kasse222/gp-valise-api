<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Support\Facades\Auth;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // ou via Policy si besoin
    }

    public function rules(): array
    {
        return [
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'currency'      => ['required', new Enum(CurrencyEnum::class)],
            'method'        => ['required', new Enum(PaymentMethodEnum::class)],
            // status géré en interne — optionnel si sécurité renforcée
            // 'status'     => ['required', new Enum(PaymentStatusEnum::class)],
            'processed_at'  => ['nullable', 'date'],
            'booking_id'    => ['required', 'exists:bookings,id'],
            // on injectera user_id automatiquement dans le controller
        ];
    }
    public function messages(): array
    {
        return [
            'user_id.in' => 'L’utilisateur de la transaction ne correspond pas à votre session.',
            'amount.min' => 'Le montant doit être supérieur à zéro.',
        ];
    }
}
