<?php

namespace App\Http\Requests\Admin;

use App\Models\District;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DistrictRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $district = $this->route('district');

        return [
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('districts', 'code')->ignore($district instanceof District ? $district->id : null),
            ],
            'name' => ['required', 'string', 'max:150'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'kode kecamatan',
            'name' => 'nama kecamatan',
            'is_active' => 'status aktif',
        ];
    }
}
