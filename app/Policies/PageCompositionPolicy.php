<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PageComposition;
use App\Models\User;

final class PageCompositionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pages.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PageComposition $pageComposition): bool
    {
        return $user->hasPermission('pages.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('pages.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PageComposition $pageComposition): bool
    {
        return $user->hasPermission('pages.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PageComposition $pageComposition): bool
    {
        return $user->hasPermission('pages.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PageComposition $pageComposition): bool
    {
        return $user->hasPermission('pages.update');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PageComposition $pageComposition): bool
    {
        return false;
    }

    public function preview(User $user, PageComposition $pageComposition): bool
    {
        return $user->hasPermission('pages.preview');
    }
}
