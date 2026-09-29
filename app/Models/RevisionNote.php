<?php

namespace App\Models;

use App\Enums\NoteStatus;
use Carbon\CarbonInterface;
use Database\Factories\RevisionNoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $review_id
 * @property string|null $field_key
 * @property int|null $attachment_id
 * @property string $body
 * @property NoteStatus $status
 * @property int|null $resolved_by
 * @property CarbonInterface|null $resolved_at
 * @property CarbonInterface|null $created_at
 * @property-read Review $review
 * @property-read Collection<int, RevisionResponse> $responses
 */
class RevisionNote extends Model
{
    /** @use HasFactory<RevisionNoteFactory> */
    use HasFactory;

    protected $fillable = ['review_id', 'field_key', 'attachment_id', 'body', 'status'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => NoteStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Review, $this> */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'review_id');
    }

    /** @return BelongsTo<VersionAttachment, $this> */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(VersionAttachment::class, 'attachment_id');
    }

    /** @return HasMany<RevisionResponse, $this> */
    public function responses(): HasMany
    {
        return $this->hasMany(RevisionResponse::class, 'revision_note_id')->orderBy('created_at');
    }

    /** @return BelongsTo<User, $this> */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /** @param Builder<$this> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', NoteStatus::Open);
    }

    public function isOpen(): bool
    {
        return $this->status === NoteStatus::Open;
    }

    public function isGeneral(): bool
    {
        return $this->field_key === null && $this->attachment_id === null;
    }
}
