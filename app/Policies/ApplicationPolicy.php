<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

final readonly class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('applications.view');
    }

    public function view(User $user, Application $application): bool
    {
        return $user->hasPermission('applications.view');
    }

    public function updateStatus(User $user, Application $application): bool
    {
        return $user->hasPermission('applications.update-status');
    }
}
