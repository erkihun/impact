# Admin Route Screen Matrix

| Route | Screen | Layout | Main shared components |
| --- | --- | --- | --- |
| `/admin` | Operations dashboard | `x-app-layout` | `x-admin.page-header`, `x-admin.kpi-card`, `x-admin.quick-action-card`, `x-ui.status-badge` |
| `/admin/content` | Content workspace | `x-app-layout` | `x-admin.page-header`, `x-admin.filter-bar`, `x-admin.result-summary`, `x-admin.content-summary-card` |
| `/admin/content/create` | Create content draft | `x-app-layout` | `x-admin.page-header`, `admin-panel`, `form-input`, `x-ui.error-summary` |
| `/admin/content/{content}` | Content detail/workflow | `x-app-layout` | `x-admin.page-header`, `admin-panel`, `workflow-rail`, `x-ui.status-badge` |
| `/admin/content/{content}/edit` | Content revision editor | `x-app-layout` | `x-admin.page-header`, `admin-panel`, `form-input`, `x-ui.error-summary` |
| `/admin/engagement` | Engagement queue | `x-app-layout` | `x-admin.page-header`, `x-admin.filter-bar`, `x-admin.record-list` |
| `/admin/engagement/{submission}` | Engagement detail | `x-app-layout` | `x-admin.page-header`, `admin-panel`, `form-input`, `x-ui.status-badge` |
| `/admin/applications` | Applications | `x-app-layout` | `x-admin.page-header`, `x-admin.filter-bar`, `x-admin.record-list` |
| `/admin/applications/{application}` | Application detail | `x-app-layout` | `x-admin.page-header`, `admin-panel`, `form-input`, `x-ui.status-badge` |
| `/admin/media` | Media library | `x-app-layout` | `x-admin.page-header`, `admin-panel`, `x-admin.record-list` |
| `/admin/users` | Users | `x-app-layout` | `x-admin.page-header`, `admin-panel`, `x-admin.filter-bar`, `x-admin.record-list` |
| `/admin/users/{user}/edit` | User access edit | `x-app-layout` | `x-admin.page-header`, `admin-panel`, `x-admin.section` |
| `/admin/roles` | Roles and permissions | `x-app-layout` | `x-admin.page-header`, `admin-panel` |
| `/admin/roles/{role}/edit` | Role edit | `x-app-layout` | `x-admin.page-header`, `admin-panel`, `x-admin.section` |
| `/admin/audit-events` | Audit events | `x-app-layout` | `x-admin.page-header`, `x-admin.filter-bar`, `x-admin.record-list` |
| `/admin/settings` | System settings | `x-app-layout` | `x-admin.page-header`, `admin-panel`, `x-admin.section` |
| `/login` | Staff login | `x-guest-layout` | Auth shell, shared button/input classes |
| `/mfa` | MFA enrollment/challenge | `x-guest-layout` | Auth shell, shared button/input classes |

Authorization remains in routes, policies, middleware, and controller queries. Navigation visibility still comes from `AppServiceProvider`.
