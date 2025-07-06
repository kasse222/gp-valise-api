<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\PaymentStatusEnum;
use App\Models\Transaction;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {

        return $this->user()?->can('refund', $this->transaction());
    }

    public function rules(): array
    {
        return [
            'status'       => ['sometimes', new Enum(PaymentStatusEnum::class)],
            'processed_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'Le statut de la transaction est invalide.',
        ];
    }
    public function transaction(): Transaction
    {
        return $this->route('transaction');
    }
}
