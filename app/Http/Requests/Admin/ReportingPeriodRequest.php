<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReportingPeriodRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'submission_deadline' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama periode',
            'starts_on' => 'tanggal mulai',
            'ends_on' => 'tanggal selesai',
            'submission_deadline' => 'batas pengiriman',
            'is_active' => 'status aktif',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'ends_on.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'submission_deadline.after_or_equal' => 'Batas pengiriman tidak boleh sebelum tanggal mulai.',
        ];
    }
}
