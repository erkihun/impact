<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** @property CarbonImmutable $starts_at */
final class Event extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'registration_closes_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<EventRegistration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function acceptsRegistrations(): bool
    {
        $closesAt = $this->getRawOriginal('registration_closes_at');

        return $this->getRawOriginal('status') === EventStatus::RegistrationOpen->value
            && ($closesAt === null || Carbon::parse((string) $closesAt)->isFuture())
            && ($this->capacity === null || $this->registrations()->where('status', 'confirmed')->count() < $this->capacity);
    }
}
