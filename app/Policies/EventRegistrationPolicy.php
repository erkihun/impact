<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventRegistration;
use App\Models\User;

final readonly class EventRegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('events.registrations.view');
    }

    public function view(User $user, EventRegistration $eventRegistration): bool
    {
        return $user->hasPermission('events.registrations.view');
    }

    public function update(User $user, EventRegistration $eventRegistration): bool
    {
        return $user->hasPermission('events.manage');
    }
}
