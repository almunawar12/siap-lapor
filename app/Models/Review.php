<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Carbon\CarbonInterface;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $report_version_id
 * @property int $reviewer_id
 * @property ReviewStatus $status
 * @property string|null $general_note
 * @property CarbonInterface $started_at
 * @property CarbonInterface|null $decided_at
 * @property-read ReportVersion $version
 * @property-read User $reviewer
 * @property-read Collection<int, RevisionNote> $notes
 */
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $fillable = ['report_version_id', 'reviewer_id', 'status', 'general_note', 'started_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'started_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ReportVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ReportVersion::class, 'report_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return HasMany<RevisionNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(RevisionNote::class, 'review_id');
    }

    /** @param Builder<$this> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ReviewStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === ReviewStatus::Active;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->reviewer_id === $user->id;
    }
}
