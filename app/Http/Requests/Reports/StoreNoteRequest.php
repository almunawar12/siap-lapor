<?php

namespace App\Http\Requests\Reports;

use App\Support\ReportPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Catatan revisi boleh umum, terkait field tertentu, atau terkait lampiran
 * tertentu; field dan lampiran tidak boleh diisi bersamaan.
 */
class StoreNoteRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'body' => ['required', 'string', 'min:3', 'max:5000'],
            'field_key' => [
                'nullable', 'string',
                Rule::in(ReportPayload::INPUT_KEYS),
                'prohibits:attachment_id',
            ],
            'attachment_id' => ['nullable', 'integer'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'body' => 'isi catatan',
            'field_key' => 'field yang dirujuk',
            'attachment_id' => 'lampiran yang dirujuk',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'field_key.prohibits' => 'Catatan tidak boleh merujuk field dan lampiran sekaligus.',
            'field_key.in' => 'Field yang dirujuk tidak dikenal.',
        ];
    }

    public function fieldKey(): ?string
    {
        $value = $this->validated('field_key');

        return $value === null || $value === '' ? null : (string) $value;
    }

    public function attachmentId(): ?int
    {
        $value = $this->validated('attachment_id');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function expectedLockVersion(): int
    {
        return (int) $this->validated('lock_version');
    }
}
