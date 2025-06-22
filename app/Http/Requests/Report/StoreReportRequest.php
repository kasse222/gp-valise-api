<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'reportable_id'   => ['required', 'integer'],
            'reportable_type' => ['required', Rule::in([
                'App\Models\Trip',
                'App\Models\Booking',
                'App\Models\User',
                // ➕ Ajouter d'autres entités si nécessaire
            ])],
            'reason'  => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
