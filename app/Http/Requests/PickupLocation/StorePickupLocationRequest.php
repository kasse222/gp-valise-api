<?php

declare(strict_types=1);

namespace App\Http\Requests\PickupLocation;

use Illuminate\Foundation\Http\FormRequest;

class StorePickupLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude'              => ['required', 'numeric', 'between:-90,90'],
            'longitude'             => ['required', 'numeric', 'between:-180,180'],
            'approximate_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'approximate_longitude' => ['required', 'numeric', 'between:-180,180'],
            'address'               => ['required', 'string', 'max:500'],
            'city'                  => ['required', 'string', 'max:100'],
            'instructions'          => ['nullable', 'string', 'max:1000'],
        ];
    }
}
