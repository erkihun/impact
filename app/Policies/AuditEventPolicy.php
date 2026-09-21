<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditEvent;
use App\Models\User;

final readonly class AuditEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function view(User $user, AuditEvent $event): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function update(User $user, AuditEvent $event): bool
    {
        return false;
    }

    public function delete(User $user, AuditEvent $event): bool
    {
        return false;
    }
}
