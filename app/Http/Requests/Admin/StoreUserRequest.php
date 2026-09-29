<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Hanya akun Admin Kecamatan yang dapat dibuat lewat modul ini. Role tidak
 * pernah diambil dari payload; lihat StoreUserController.
 */
class StoreUserRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where('is_active', true)],
            'password' => ['required', 'string', 'confirmed', Password::default()->min(8)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'district_id' => 'kecamatan',
            'password' => 'kata sandi sementara',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->string('email')))]);
        }
    }
}
