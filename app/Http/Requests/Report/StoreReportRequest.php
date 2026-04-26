<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ReportReasonEnum;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'reportable_id'   => ['required', 'integer'],
            'reportable_type' => ['required', 'string', 'max:255'],
            'reason'          => ['required', new Enum(ReportReasonEnum::class)],
            'details'         => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Merci de préciser un motif de signalement.',
            'reason.enum'     => 'Le motif est invalide.',
        ];
    }
}
