<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Carbon\CarbonInterface;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property int $district_id
 * @property int $reporting_period_id
 * @property int $created_by
 * @property string|null $report_number
 * @property ReportStatus $status
 * @property int|null $current_version_id
 * @property int|null $approved_version_id
 * @property int $lock_version
 * @property CarbonInterface|null $first_submitted_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read District $district
 * @property-read ReportingPeriod $period
 * @property-read ReportVersion|null $currentVersion
 * @property-read Collection<int, ReportVersion> $versions
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    /**
     * Status, pointer versi, lock_version, dan district sengaja tidak fillable:
     * seluruhnya ditetapkan action di server, bukan payload request.
     *
     * @var list<string>
     */
    protected $fillable = ['report_number'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'first_submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<District, $this> */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /** @return BelongsTo<ReportingPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class, 'reporting_period_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<ReportVersion, $this> */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ReportVersion::class, 'current_version_id');
    }

    /** @return BelongsTo<ReportVersion, $this> */
    public function approvedVersion(): BelongsTo
    {
        return $this->belongsTo(ReportVersion::class, 'approved_version_id');
    }

    /** @return HasMany<ReportVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ReportVersion::class)->orderBy('version_number');
    }

    /** @return HasManyThrough<Review, ReportVersion, $this> */
    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(
            Review::class,
            ReportVersion::class,
            'report_id',
            'report_version_id',
        );
    }

    /**
     * Seluruh catatan revisi laporan ini, termasuk siklus pemeriksaan lama.
     * Persetujuan ditolak bila masih ada catatan terbuka dari siklus mana pun
     * (PRD bagian 7).
     *
     * @return Builder<RevisionNote>
     */
    public function notesQuery(): Builder
    {
        return RevisionNote::query()->whereIn(
            'review_id',
            $this->reviews()->getQuery()->select('reviews.id'),
        );
    }

    /**
     * Membatasi query sesuai cakupan wilayah pengguna. Admin Kecamatan hanya
     * boleh melihat kecamatannya sendiri (PRD AC01).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isKecamatan()) {
            $query->where('district_id', $user->district_id);
        }
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }
}
