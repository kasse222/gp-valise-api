<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentMethodEnum;
use Illuminate\Validation\Rules\Enum;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); // Possibilité d'ajouter une Policy ici si besoin
    }

    public function rules(): array
    {
        return [
            'amount'         => ['sometimes', 'numeric', 'min:0.01'],
            'currency'       => ['sometimes', 'string', 'size:3'],
            'status'         => ['sometimes', new Enum(TransactionStatusEnum::class)],
            'method'         => ['sometimes', new Enum(PaymentMethodEnum::class)],
            'processed_at'   => ['nullable', 'date'],
        ];
    }
}
