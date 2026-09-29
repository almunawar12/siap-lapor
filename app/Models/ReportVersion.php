<?php

namespace App\Models;

use App\Support\ReportPayload;
use Carbon\CarbonInterface;
use Database\Factories\ReportVersionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $report_id
 * @property int $version_number
 * @property array<string, string|null> $payload
 * @property int $schema_version
 * @property CarbonInterface|null $submitted_at
 * @property int $created_by
 * @property-read Report $report
 * @property-read Collection<int, VersionAttachment> $attachments
 */
class ReportVersion extends Model
{
    /** @use HasFactory<ReportVersionFactory> */
    use HasFactory;

    protected $fillable = ['report_id', 'version_number', 'payload', 'schema_version', 'created_by'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Report, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'report_version_id');
    }

    /**
     * Pemeriksaan aktif versi ini. Partial unique index di database menjamin
     * paling banyak satu baris aktif.
     *
     * @return HasOne<Review, $this>
     */
    public function activeReview(): HasOne
    {
        return $this->hasOne(Review::class, 'report_version_id')->active();
    }

    /** @return HasMany<VersionAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(VersionAttachment::class, 'report_version_id');
    }

    /**
     * Versi yang sudah dikirim bersifat immutable (PRD bagian 6).
     */
    public function isEditable(): bool
    {
        return $this->submitted_at === null;
    }

    /**
     * Payload selalu dikembalikan lengkap dengan seluruh key kanonik, walaupun
     * baris lama disimpan sebelum sebuah key ditambahkan.
     *
     * @return array<string, string|null>
     */
    public function normalizedPayload(): array
    {
        return [...ReportPayload::empty(), ...ReportPayload::normalize($this->payload ?? [])];
    }
}
