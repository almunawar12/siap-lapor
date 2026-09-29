<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $report_id
 * @property int|null $actor_id
 * @property string $event
 * @property string|null $from_status
 * @property string|null $to_status
 * @property int|null $report_version_id
 * @property array<string, mixed> $metadata
 * @property CarbonInterface $created_at
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'report_id', 'actor_id', 'event', 'from_status', 'to_status', 'report_version_id', 'metadata',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return BelongsTo<Report, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
