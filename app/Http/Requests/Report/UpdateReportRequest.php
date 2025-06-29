<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ReportReasonEnum;
use App\Models\Report;


class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = $this->user();

        $report = $this->route('report');

        return $user && (
            $report?->user_id === $user->id || $user->isAdmin()
        );
    }

    public function rules(): array
    {
        return [
            'reason'  => ['sometimes', new Enum(ReportReasonEnum::class)],
            'details' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
