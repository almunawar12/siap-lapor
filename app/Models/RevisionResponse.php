<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\RevisionResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $revision_note_id
 * @property int $report_version_id
 * @property int $author_id
 * @property string $body
 * @property CarbonInterface $created_at
 * @property-read User $author
 */
class RevisionResponse extends Model
{
    /** @use HasFactory<RevisionResponseFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['revision_note_id', 'report_version_id', 'author_id', 'body'];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<ReportVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ReportVersion::class, 'report_version_id');
    }
}
