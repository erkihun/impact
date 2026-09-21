<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;

final readonly class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('media.view');
    }

    public function view(User $user, MediaAsset $mediaAsset): bool
    {
        $applicationFile = $mediaAsset->applicationFiles()->first();
        if ($applicationFile !== null) {
            return $user->hasPermission('applications.view');
        }

        $submissionFile = $mediaAsset->submissionFiles()->with('submission')->first();
        if ($submissionFile !== null) {
            $submission = $submissionFile->submission;

            return $user->hasPermission('engagement.view')
                && ($submission->assigned_to === null
                    || (string) $submission->assigned_to === (string) $user->getKey()
                    || $user->hasPermission('engagement.assign'));
        }

        return $user->hasPermission('media.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('media.create');
    }

    public function update(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->hasPermission('media.create');
    }

    public function approve(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->hasPermission('media.approve');
    }

    public function delete(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->hasPermission('media.delete')
            && ! $mediaAsset->variants()->exists();
    }
}
