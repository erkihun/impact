# Settings security model

## Authorization

All Settings Center routes are inside the existing admin route group:

- `auth`
- `verified`
- `session.current`
- `mfa`
- `permission:settings.manage`

## Recent MFA

Sensitive category update and reset routes use `recent_mfa:15`.

The middleware checks the selected category and activates only when any setting in that category has `recent_mfa: true`.

Sensitive categories include security, authentication, privacy/retention, maintenance, integrations where security-affecting, and feature flags where high risk.

## Change reason

Sensitive categories require `change_reason` with 10-500 characters.

Change reason is stored on the setting row and in immutable audit metadata.

## Environment-managed values

Environment-managed values:

- are displayed read-only;
- show source as `Managed by environment`;
- are excluded from category validation rules;
- trigger unknown-setting validation if submitted;
- are skipped in the update action.

## Secrets

The registry does not store raw provider passwords, tokens, API keys, database credentials, object storage credentials, or encryption keys.

Provider credentials remain in configuration/environment. Settings pages display provider status only.

## Audit

Every persisted setting update and reset writes immutable audit events with:

- key
- category
- translated label text
- classification
- change reason
- safe previous value
- safe new value
- effect labels
- reset status
- correlation ID
- actor
