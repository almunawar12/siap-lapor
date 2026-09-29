<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Submit tidak membawa isi laporan: yang dikirim adalah versi kerja yang sudah
 * tersimpan. Payload hanya memuat penanda konkurensi.
 */
class SubmitReportRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_version_id' => ['required', 'integer'],
            'lock_version' => ['required', 'integer', 'min:0'],
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
}
