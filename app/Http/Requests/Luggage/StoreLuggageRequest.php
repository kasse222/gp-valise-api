<?php

namespace App\Http\Requests\Luggage;

use App\Enums\LuggageCategoryEnum;
use App\Enums\LuggageStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLuggageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trip_id'             => ['required', 'exists:trips,id'],
            'description'         => ['nullable', 'string', 'max:1000'],
            'category'            => ['nullable', Rule::enum(LuggageCategoryEnum::class)],
            'weight_kg'           => ['required', 'numeric', 'min:1', 'max:1000'],
            'length_cm'           => ['required', 'numeric', 'min:1', 'max:200'],
            'width_cm'            => ['required', 'numeric', 'min:1', 'max:200'],
            'height_cm'           => ['required', 'numeric', 'min:1', 'max:200'],
            'pickup_city'         => ['required', 'string', 'max:100'],
            'delivery_city'       => ['required', 'string', 'max:100'],
            'pickup_date'         => ['required', 'date', 'after_or_equal:today'],
            'delivery_date'       => ['required', 'date', 'after:pickup_date'],
            'status'              => ['nullable', Rule::in(LuggageStatusEnum::values())],
            'is_fragile'          => ['nullable', 'boolean'],
            'insurance_requested' => ['nullable', 'boolean'],
            'photo_path'          => ['nullable', 'string', 'max:500'],
            'content_items'       => ['nullable', 'array'],
            'content_items.*.category'    => ['required', Rule::enum(LuggageCategoryEnum::class)],
            'content_items.*.description' => ['required', 'string', 'max:500'],
            'content_items.*.photo_path'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
