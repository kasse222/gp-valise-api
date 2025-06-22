<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'reason'  => ['sometimes', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
