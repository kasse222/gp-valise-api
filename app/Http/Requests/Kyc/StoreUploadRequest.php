<?php

declare(strict_types=1);

namespace App\Http\Requests\Kyc;

use Illuminate\Foundation\Http\FormRequest;

class StoreUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'    => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
            'context' => ['required', 'string', 'in:kyc,luggage'],
        ];
    }
}
