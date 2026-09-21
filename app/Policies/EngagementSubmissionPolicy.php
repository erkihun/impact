<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EngagementSubmission;
use App\Models\User;

final readonly class EngagementSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('engagement.view');
    }

    public function view(User $user, EngagementSubmission $submission): bool
    {
        return $user->hasPermission('engagement.view')
            && ($submission->assigned_to === null
                || (string) $submission->assigned_to === (string) $user->getKey()
                || $user->hasPermission('engagement.assign'));
    }

    public function assign(User $user, EngagementSubmission $submission): bool
    {
        return $user->hasPermission('engagement.assign');
    }
}
