<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Penanda konkurensi untuk seluruh tindakan pemeriksaan. Catatan umum opsional;
 * alasan wajib divalidasi oleh request turunannya.
 */
class ReviewActionRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_version_id' => ['required', 'integer'],
            'lock_version' => ['required', 'integer', 'min:0'],
            'general_note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['general_note' => 'catatan umum'];
    }

    public function expectedVersionId(): int
    {
        return (int) $this->validated('current_version_id');
    }

    public function expectedLockVersion(): int
    {
        return (int) $this->validated('lock_version');
    }

    public function generalNote(): ?string
    {
        $note = trim((string) $this->validated('general_note'));

        return $note === '' ? null : $note;
    }
}
