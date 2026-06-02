<?php

declare(strict_types=1);

namespace App\Http\Requests\WaitlistEmail;

use Illuminate\Foundation\Http\FormRequest;

class WaitlistEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'   => ['required', 'email', 'unique:waitlist_emails,email'],
            'name'    => ['nullable', 'string', 'max:100'],
            'role'    => ['nullable', 'string', 'in:sender,traveler,curious'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
