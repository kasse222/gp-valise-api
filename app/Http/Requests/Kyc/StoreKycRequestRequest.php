<?php

declare(strict_types=1);

namespace App\Http\Requests\Kyc;

use Illuminate\Foundation\Http\FormRequest;

class StoreKycRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_photo_path'     => ['required', 'string', 'max:500'],
            'parcel_photo_path' => ['required', 'string', 'max:500'],
        ];
    }
}
