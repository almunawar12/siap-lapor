<?php

namespace App\Models;

use App\Enums\UserRole;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property int|null $district_id
 * @property bool $is_active
 * @property bool $must_change_password
 * @property CarbonInterface|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read District|null $district
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Sengaja tanpa `role`, `district_id`, dan `is_active`: nilai tersebut hanya
     * boleh ditetapkan secara eksplisit oleh server, tidak dari payload request.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password'];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Email disimpan dalam huruf kecil agar unique index bersifat case-insensitive.
     */
    /** @return Attribute<string, string> */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => mb_strtolower(trim($value)),
        );
    }

    /** @return BelongsTo<District, $this> */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function isKabupaten(): bool
    {
        return $this->role === UserRole::AdminKabupaten;
    }

    public function isKecamatan(): bool
    {
        return $this->role === UserRole::AdminKecamatan;
    }

    /** @param Builder<$this> $query */
    public function scopeKecamatan(Builder $query): void
    {
        $query->where('role', UserRole::AdminKecamatan);
    }
}
