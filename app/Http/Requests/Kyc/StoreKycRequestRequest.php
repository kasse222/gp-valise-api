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
            'id_front_path' => ['required', 'string', 'max:500'],
            'id_back_path'  => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_front_path.required' => 'La photo recto de votre pièce d\'identité est obligatoire.',
        ];
    }
}
