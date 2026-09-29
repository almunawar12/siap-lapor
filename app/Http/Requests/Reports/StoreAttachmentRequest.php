<?php

namespace App\Http\Requests\Reports;

use App\Enums\AttachmentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttachmentRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_version_id' => ['required', 'integer'],
            'lock_version' => ['required', 'integer', 'min:0'],
            'category' => ['required', Rule::in(AttachmentCategory::values())],
            'description' => ['nullable', 'string', 'max:500'],
            'file' => [
                'required',
                'file',
                // `mimetypes` memeriksa isi berkas, bukan hanya ekstensi.
                'mimetypes:'.implode(',', (array) config('siaplapor.attachments.mimes')),
                'max:'.config('siaplapor.attachments.max_size_kb'),
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'file' => 'berkas lampiran',
            'category' => 'kategori lampiran',
            'description' => 'keterangan lampiran',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $maxMb = round(((int) config('siaplapor.attachments.max_size_kb')) / 1024, 1);

        return [
            'file.mimetypes' => 'Lampiran hanya boleh berupa PDF, JPEG, atau PNG.',
            'file.max' => "Ukuran lampiran maksimal {$maxMb} MB.",
        ];
    }

    public function category(): AttachmentCategory
    {
        return AttachmentCategory::from((string) $this->validated('category'));
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
