<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ReportingPeriodFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property CarbonInterface $starts_on
 * @property CarbonInterface $ends_on
 * @property CarbonInterface|null $submission_deadline
 * @property bool $is_active
 */
class ReportingPeriod extends Model
{
    /** @use HasFactory<ReportingPeriodFactory> */
    use HasFactory;

    protected $fillable = ['name', 'starts_on', 'ends_on', 'submission_deadline', 'is_active'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'submission_deadline' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /** @param Builder<$this> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Penanda terlambat tidak menggantikan status alur (PRD bagian 6).
     */
    public function isLate(CarbonInterface $at): bool
    {
        return $this->submission_deadline !== null
            && $at->startOfDay()->greaterThan($this->submission_deadline);
    }
}
