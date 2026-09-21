# Settings route and screen matrix

| Route | Name | Screen | Purpose |
|---|---|---|---|
| `GET /admin/settings` | `admin.settings.edit` | Overview | Search, environment summary, category cards, recent changes |
| `GET /admin/settings/history` | `admin.settings.history` | History | Filtered immutable configuration audit history |
| `GET /admin/settings/{category}` | `admin.settings.show` | Category editor | Edit one registered category |
| `PUT /admin/settings/{category}` | `admin.settings.update` | Category save | Validate and save one category only |
| `POST /admin/settings/{category}/reset` | `admin.settings.reset` | Category reset | Restore non-environment defaults for one category |

All routes require `settings.manage`. Update and reset routes also pass through `recent_mfa:15`.
