<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VacancyStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

final class Vacancy extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => VacancyStatus::class,
            'opens_at' => 'immutable_datetime',
            'closes_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function acceptsApplications(): bool
    {
        $opensAt = $this->getRawOriginal('opens_at');
        $closesAt = $this->getRawOriginal('closes_at');

        return $this->getRawOriginal('status') === VacancyStatus::Published->value
            && ($opensAt === null || Carbon::parse((string) $opensAt)->isPast())
            && ($closesAt === null || Carbon::parse((string) $closesAt)->isFuture())
            && ($this->application_limit === null || $this->applications()->count() < $this->application_limit);
    }
}
