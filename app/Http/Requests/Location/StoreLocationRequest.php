<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\LocationPositionEnum;
use App\Enums\LocationTypeEnum;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ✅ La policy est gérée dans le contrôleur
    }

    public function rules(): array
    {
        return [
            'trip_id'     => ['required', 'exists:trips,id'],
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
            'city'        => ['required', 'string', 'max:100'],
            'order_index' => ['required', 'integer', 'min:0'],
            'position'    => ['required', new Enum(LocationPositionEnum::class)],
            'type'        => ['required', new Enum(LocationTypeEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'trip_id.required' => 'Le trajet associé est requis.',
            'trip_id.exists'   => 'Le trajet spécifié est invalide.',
            'latitude.*'       => 'La latitude doit être un nombre entre -90 et 90.',
            'longitude.*'      => 'La longitude doit être un nombre entre -180 et 180.',
            'city.required'    => 'La ville est requise.',
            'order_index.*'    => 'L’ordre doit être un entier positif.',
            'position.required' => 'La position (départ, étape, arrivée) est requise.',
            'type.required'    => 'Le type de lieu est requis.',
        ];
    }
}
