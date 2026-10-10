<?php

declare(strict_types=1);

namespace App\Support\Inertia;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\EngagementSubmission;
use App\Models\User;
use App\Support\Settings\EffectiveSettings;
use App\Support\Settings\PublicUiSettings;
use Illuminate\Http\Request;

final class WorkspaceShell
{
    /** Request statuses shown under New: nobody has started on them. */
    public const NEW_REQUEST_STATUSES = ['received', 'scanning', 'triaged', 'quarantined'];

    public static function data(Request $request): array
    {
        $user = $request->user();
        $permissions = [];
        if ($user instanceof User) {
            $user->loadMissing('roles.permissions');
            $permissions = $user->roles->flatMap->permissions->pluck('code')->unique()->values()->all();
        }
        $settings = app(EffectiveSettings::class);

        return [
            'user' => $user?->only(['id', 'name', 'email', 'locale', 'email_verified_at']),
            'privileged' => $user instanceof User && $user->isPrivileged(),
            'permissions' => $permissions,
            'identity' => app(PublicUiSettings::class)->identity(),
            'filters' => $request->query(),
            'locale' => app()->getLocale(),
            'sidebarDefault' => $settings->string('appearance.default_admin_sidebar_state'),
            'loginNotice' => $settings->nullableString('security.login_notice'),
            'securityEmail' => $settings->nullableString('security.contact_email'),
            'inbox' => $user instanceof User ? self::inbox($user) : [],
            'text' => [],
        ];
    }

    /**
     * Sidebar badges: requests and applications nobody has picked up yet.
     *
     * @return array{requests?: int, applications?: int}
     */
    private static function inbox(User $user): array
    {
        return array_filter([
            'requests' => $user->hasPermission('engagement.view')
                ? EngagementSubmission::query()->whereIn('status', self::NEW_REQUEST_STATUSES)->count()
                : null,
            'applications' => $user->hasPermission('applications.view')
                ? Application::query()->where('status', ApplicationStatus::Received->value)->count()
                : null,
        ], static fn (?int $count): bool => $count !== null);
    }
}
