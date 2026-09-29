<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class StoreResponseRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'body' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['body' => 'tanggapan'];
    }

    public function expectedLockVersion(): int
    {
        return (int) $this->validated('lock_version');
    }
}
