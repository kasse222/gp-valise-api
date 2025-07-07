<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\LocationPositionEnum;
use App\Enums\LocationTypeEnum;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // 🔐 Ou appeler la Policy LocationPolicy::update()
    }

    public function rules(): array
    {
        return [
            'latitude'    => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude'   => ['sometimes', 'numeric', 'between:-180,180'],
            'city'        => ['sometimes', 'string', 'max:100'],
            'order_index' => ['sometimes', 'integer', 'min:0'],
            'position'    => ['sometimes', new Enum(LocationPositionEnum::class)],
            'type'        => ['sometimes', new Enum(LocationTypeEnum::class)],
        ];
    }
}
