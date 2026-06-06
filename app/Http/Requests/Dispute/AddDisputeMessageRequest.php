<?php

declare(strict_types=1);

namespace App\Http\Requests\Dispute;

use Illuminate\Foundation\Http\FormRequest;

class AddDisputeMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'          => ['required', 'string', 'min:1', 'max:2000'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['string', 'max:500'],
        ];
    }
}
