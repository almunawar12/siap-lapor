<?php

namespace Database\Factories;

use App\Enums\AttachmentCategory;
use App\Models\File;
use App\Models\ReportVersion;
use App\Models\VersionAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VersionAttachment>
 */
class VersionAttachmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'report_version_id' => ReportVersion::factory(),
            'file_id' => File::factory(),
            'category' => AttachmentCategory::Lainnya->value,
            'description' => null,
        ];
    }
}
