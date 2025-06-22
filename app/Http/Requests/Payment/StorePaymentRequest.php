<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isExpeditor();
    }

    public function rules(): array
    {
        return [
            'booking_id'  => ['required', 'exists:bookings,id'],
            'amount'      => ['required', 'numeric', 'min:1'],
            'method'      => ['required', new Enum(PaymentMethodEnum::class)],
            'status'      => ['required', new Enum(PaymentStatusEnum::class)],
            'paid_at'     => ['nullable', 'date'],
        ];
    }
}
