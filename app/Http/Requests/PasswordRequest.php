<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::default()->min(8)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'current_password' => 'kata sandi saat ini',
            'password' => 'kata sandi baru',
        ];
    }
}
