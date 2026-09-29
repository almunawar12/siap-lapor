<?php

namespace App\Models;

use App\Enums\AttachmentCategory;
use Database\Factories\VersionAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $report_version_id
 * @property int $file_id
 * @property AttachmentCategory $category
 * @property string|null $description
 * @property int|null $replaces_attachment_id
 * @property-read File $file
 * @property-read ReportVersion $version
 */
class VersionAttachment extends Model
{
    /** @use HasFactory<VersionAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'report_version_id', 'file_id', 'category', 'description', 'replaces_attachment_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['category' => AttachmentCategory::class];
    }

    /** @return BelongsTo<File, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /** @return BelongsTo<ReportVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ReportVersion::class, 'report_version_id');
    }
}
