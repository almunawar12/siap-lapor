<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class NoteDecisionRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }

    public function expectedLockVersion(): int
    {
        return (int) $this->validated('lock_version');
    }
}
