<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Hanya periode yang berasal dari payload. Kecamatan, pembuat, dan status
 * ditetapkan server (PRD AC10).
 */
class StoreReportRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reporting_period_id' => [
                'required', 'integer',
                Rule::exists('reporting_periods', 'id')->where('is_active', true),
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['reporting_period_id' => 'periode pelaporan'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reporting_period_id.exists' => 'Periode pelaporan tidak ditemukan atau sudah tidak aktif.',
        ];
    }
}
