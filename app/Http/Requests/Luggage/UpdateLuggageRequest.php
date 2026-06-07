<?php

namespace App\Http\Requests\Luggage;

use App\Enums\LuggageCategoryEnum;
use App\Enums\LuggageStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateLuggageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'trip_id'             => ['sometimes', 'exists:trips,id'],
            'description'         => ['sometimes', 'string', 'max:1000'],
            'category'            => ['sometimes', Rule::enum(LuggageCategoryEnum::class)],
            'weight_kg'           => ['sometimes', 'numeric', 'min:0.1', 'max:100'],
            'length_cm'           => ['sometimes', 'numeric', 'min:1', 'max:200'],
            'width_cm'            => ['sometimes', 'numeric', 'min:1', 'max:200'],
            'height_cm'           => ['sometimes', 'numeric', 'min:1', 'max:200'],
            'pickup_city'         => ['sometimes', 'string', 'max:100'],
            'delivery_city'       => ['sometimes', 'string', 'max:100'],
            'pickup_date'         => ['sometimes', 'date', 'after_or_equal:today'],
            'delivery_date'       => ['sometimes', 'date', 'after:pickup_date'],
            'status'              => ['sometimes', Rule::in(LuggageStatusEnum::values())],
            'is_fragile'          => ['sometimes', 'boolean'],
            'insurance_requested' => ['sometimes', 'boolean'],
            'photo_path'          => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
