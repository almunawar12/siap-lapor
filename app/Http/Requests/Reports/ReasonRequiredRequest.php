<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ambil alih pemeriksaan dan buka kembali laporan disetujui: alasan wajib dan
 * tercatat pada audit.
 */
class ReasonRequiredRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_version_id' => ['required', 'integer'],
            'lock_version' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['reason' => 'alasan'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan wajib diisi dan akan tercatat pada riwayat.',
            'reason.min' => 'Alasan minimal 10 karakter agar dapat ditelusuri.',
        ];
    }

    public function expectedVersionId(): int
    {
        return (int) $this->validated('current_version_id');
    }

    public function expectedLockVersion(): int
    {
        return (int) $this->validated('lock_version');
    }

    public function reason(): string
    {
        return trim((string) $this->validated('reason'));
    }
}
