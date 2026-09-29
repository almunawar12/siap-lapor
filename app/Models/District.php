<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\DistrictFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class District extends Model
{
    /** @use HasFactory<DistrictFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @param Builder<$this> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
