<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentMethodEnum;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'booking_id'   => ['required', 'exists:bookings,id'],
            'amount'       => ['required', 'numeric', 'min:0'],
            'currency'     => ['required', 'string', 'size:3'], // ex: EUR, USD
            'status'       => ['required', new Enum(TransactionStatusEnum::class)],
            'method'       => ['required', new Enum(PaymentMethodEnum::class)],
            'processed_at' => ['nullable', 'date'],
        ];
    }
}
