# Settings registry

The Settings Center uses `App\Support\SettingCatalog` as the controlled typed registry.

## Registry metadata

Each setting definition includes:

- key
- category
- group
- label
- description
- type
- validation rules
- UI input
- allowed options where applicable
- scope default
- sensitivity default
- environment override support
- recent MFA requirement
- change reason requirement
- audit classification
- effect labels
- display order

## Enums

Implemented enums:

- `App\Enums\Settings\SettingType`
- `App\Enums\Settings\SettingCategory`
- `App\Enums\Settings\SettingScope`
- `App\Enums\Settings\SettingSensitivity`
- `App\Enums\Settings\SettingEffect`

## Registry size

- Definitions: 167
- Defaults: 167
- Consistency check: no missing default or definition keys.

## Categories

Implemented routes and category metadata for:

- General
- Branding and Identity
- Appearance
- Localization
- Security
- Authentication
- Notifications
- Email
- Integrations
- Privacy and Retention
- Content and Publishing
- SEO and Social Sharing
- Engagement Forms
- Media and Uploads
- Search
- Performance and Cache
- Maintenance
- Feature Flags
- Environment
