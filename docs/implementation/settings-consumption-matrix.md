# Settings consumption matrix

| Setting area | Consumed by | Evidence |
|---|---|---|
| Organization display/legal/short names | Public layout and admin layout/navigation | `resources/views/layouts/public.blade.php`, `resources/views/layouts/app.blade.php`, `resources/views/layouts/navigation.blade.php` |
| SEO title/description/robots | Public layout metadata | `resources/views/layouts/public.blade.php` |
| Branding tagline | Public footer and branding preview | `resources/views/layouts/public.blade.php`, `resources/views/admin/settings/show.blade.php` |
| Copyright owner | Public footer | `resources/views/layouts/public.blade.php` |
| Environment-managed settings | Effective settings resolver and category UI | `App\Support\Settings\EffectiveSettings`, category view |
| Security MFA/reason metadata | Middleware, Form Request, Action, audit history | `EnsureRecentMfa`, `UpdateSettingsGroupRequest`, `UpdateSettingsAction`, `SettingHistoryController` |
| Notification toggles | Settings UI and notification matrix | `resources/views/admin/settings/show.blade.php` |

## Remaining consumption work

Some settings are currently governed policy records with safe UI and audit, but still need deeper module-level enforcement in dedicated follow-up work:

- full password rule customization;
- notification dispatch suppression by event/channel;
- public form enable/disable gates;
- upload limit lower-bound enforcement across every form;
- feature flag service integration;
- maintenance banner rendering on public pages.
