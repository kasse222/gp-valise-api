<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\PaymentStatusEnum;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'status'   => ['required', new Enum(PaymentStatusEnum::class)],
            'paid_at'  => ['nullable', 'date'],
        ];
    }
}
