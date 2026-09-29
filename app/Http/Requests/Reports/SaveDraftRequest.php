<?php

namespace App\Http\Requests\Reports;

use App\Enums\SignerCapacity;
use App\Models\Report;
use App\Support\ReportPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi simpan draf. Draf boleh belum lengkap (PRD bagian 5), jadi seluruh
 * field bersifat nullable, tetapi format dan batas panjang tetap ditegakkan.
 *
 * Hanya key pada ReportPayload::INPUT_KEYS yang diterima. `district_name`,
 * `institution_name`, `regency_name`, status, dan reviewer tidak pernah berasal
 * dari payload.
 */
class SaveDraftRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $report = $this->route('report');
        $max = ReportPayload::MAX_LENGTHS;

        return [
            'current_version_id' => ['required', 'integer'],
            'lock_version' => ['required', 'integer', 'min:0'],

            'report_number' => [
                'nullable', 'string', 'max:'.$max['report_number'],
                Rule::unique('reports', 'report_number')
                    ->ignore($report instanceof Report ? $report->id : null),
            ],

            'supervisor_name' => ['nullable', 'string', 'max:'.$max['supervisor_name']],
            'supervisor_position' => ['nullable', 'string', 'max:'.$max['supervisor_position']],
            'assignment_number' => ['nullable', 'string', 'max:'.$max['assignment_number']],
            'assignment_date' => ['nullable', 'date_format:Y-m-d'],
            'supervisor_address' => ['nullable', 'string', 'max:'.$max['supervisor_address']],

            'activity_name' => ['nullable', 'string', 'max:'.$max['activity_name']],
            'activity_form' => ['nullable', 'string', 'max:'.$max['activity_form']],
            'activity_purpose' => ['nullable', 'string', 'max:'.$max['activity_purpose']],
            'activity_target' => ['nullable', 'string', 'max:'.$max['activity_target']],
            'activity_start_date' => ['nullable', 'date_format:Y-m-d'],
            // Satu-satunya aturan tanggal yang disepakati PRD: selesai >= mulai.
            'activity_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:activity_start_date'],
            'activity_start_time' => ['nullable', 'date_format:H:i'],
            'activity_end_time' => ['nullable', 'date_format:H:i'],
            'activity_location' => ['nullable', 'string', 'max:'.$max['activity_location']],

            'findings' => ['nullable', 'string', 'max:'.$max['findings']],

            'signing_place' => ['nullable', 'string', 'max:'.$max['signing_place']],
            'signing_date' => ['nullable', 'date_format:Y-m-d'],
            'signer_name' => ['nullable', 'string', 'max:'.$max['signer_name']],
            'signer_capacity' => ['nullable', Rule::in(SignerCapacity::values())],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ReportPayload::labels();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'activity_end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'report_number.unique' => 'Nomor LHP ini sudah dipakai laporan lain.',
            'assignment_date.date_format' => 'Tanggal surat perintah tugas harus berformat YYYY-MM-DD.',
            'activity_start_date.date_format' => 'Tanggal mulai harus berformat YYYY-MM-DD.',
            'activity_end_date.date_format' => 'Tanggal selesai harus berformat YYYY-MM-DD.',
            'signing_date.date_format' => 'Tanggal penandatanganan harus berformat YYYY-MM-DD.',
            'activity_start_time.date_format' => 'Jam mulai harus berformat HH:MM.',
            'activity_end_time.date_format' => 'Jam selesai harus berformat HH:MM.',
        ];
    }

    /**
     * Nilai payload yang sudah lolos validasi, tanpa field kontrol.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->safe()->only(ReportPayload::INPUT_KEYS);
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
