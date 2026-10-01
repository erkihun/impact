<?php

declare(strict_types=1);

namespace App\Support\Inertia;

use App\Models\User;
use App\Support\Settings\EffectiveSettings;
use App\Support\Settings\PublicUiSettings;
use Illuminate\Http\Request;

final class WorkspaceShell
{
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
            'text' => app()->getLocale() === 'en' ? [] : json_decode(file_get_contents(lang_path(app()->getLocale().'.json')), true),
        ];
    }
}
