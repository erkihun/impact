<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\Concerns\HasBinaryUuid;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property UserStatus $status
 * @property string $locale
 * @property string|null $mfa_secret
 * @property array<int, string>|null $mfa_recovery_codes
 * @property CarbonImmutable|null $mfa_confirmed_at
 * @property bool $mfa_exempt
 * @property CarbonImmutable|null $last_login_at
 * @property CarbonImmutable|null $locked_until
 * @property CarbonImmutable|null $expires_at
 * @property int $failed_login_attempts
 * @property int $session_version
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasBinaryUuid, HasFactory, Notifiable;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
        'locale' => 'en',
        'mfa_secret' => null,
        'mfa_recovery_codes' => null,
        'mfa_confirmed_at' => null,
        'mfa_exempt' => false,
        'last_login_at' => null,
        'failed_login_attempts' => 0,
        'locked_until' => null,
        'expires_at' => null,
        'session_version' => 1,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'status',
        'mfa_secret',
        'mfa_recovery_codes',
        'mfa_confirmed_at',
        'mfa_exempt',
        'last_login_at',
        'failed_login_attempts',
        'locked_until',
        'expires_at',
        'session_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
        'mfa_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'mfa_secret' => 'encrypted',
            'mfa_recovery_codes' => 'encrypted:array',
            'mfa_confirmed_at' => 'immutable_datetime',
            'mfa_exempt' => 'boolean',
            'last_login_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /** @return HasMany<UserInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(
                fn (Role $role): bool => $role->permissions->contains('code', $permission),
            );
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('code', $permission))
            ->exists();
    }

    public function isPrivileged(): bool
    {
        return $this->roles()->exists();
    }

    public function requiresMfa(): bool
    {
        return $this->isPrivileged() && ! $this->mfa_exempt;
    }
}
