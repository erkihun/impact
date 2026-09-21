# Settings test matrix

| Requirement | Test coverage |
|---|---|
| Overview renders | `SettingsManagementTest::it renders the settings overview and category control center` |
| Category editor renders | same test, `/admin/settings/security` |
| Category-only save | `updates one non-sensitive category...` |
| Real UI behavior | public `/en` shows updated legal name |
| Unknown key rejected | `rejects unknown setting keys...` |
| Environment-managed setting cannot be changed | same test via `site__timezone` |
| Sensitive recent MFA required | `requires recent MFA...` |
| Change reason required | same test |
| Sensitive update audited | `updates sensitive settings...` |
| History renders safe metadata | same test |
| Reset only selected category | `resets only the selected category defaults` |
| Permission enforced | `denies settings changes without permission` |
| New Blade literals translated | `UiUxExperienceTest::it has an Amharic translation for every literal Blade translation key` |

## Commands run

- `php -l` on settings classes
- `php artisan route:list --name=settings`
- `php artisan view:cache`
- `php artisan test tests\Feature\Feature\SettingsManagementTest.php`
- `php artisan test tests\Feature\Feature\SettingsManagementTest.php tests\Feature\AdminUiConsistencyTest.php tests\Feature\UiUxExperienceTest.php --filter="Amharic translation|settings|admin shell"`
- `vendor\bin\phpstan analyse`
- `npm run build`
- `php artisan test --stop-on-failure` fails only on missing local `Imagick` extension in `MediaLifecycleTest`
