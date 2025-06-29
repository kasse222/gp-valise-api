<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\PaymentStatusEnum;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorisation basique pour le MVP (à affiner avec Policy si admin uniquement)
        return Auth::check();
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
}
