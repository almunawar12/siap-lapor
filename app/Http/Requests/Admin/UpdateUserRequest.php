<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Kecamatan tidak dapat dipindahkan pada MVP (lihat PRD.md bagian 4), sehingga
 * `district_id` sengaja tidak menjadi field yang dapat diubah di sini.
 */
class UpdateUserRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($target instanceof User ? $target->id : null),
            ],
            'password' => ['nullable', 'string', 'confirmed', Password::default()->min(8)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'password' => 'kata sandi baru',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->string('email')))]);
        }
    }
}
