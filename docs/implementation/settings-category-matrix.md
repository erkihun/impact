# Settings category matrix

| Navigation group | Category | Route | Controls | Sensitive controls |
|---|---|---:|---:|---:|
| Organization | General | `/admin/settings/general` | 18 | Some environment-managed |
| Organization | Branding and Identity | `/admin/settings/branding` | 13 | No secrets |
| Experience | Appearance | `/admin/settings/appearance` | 15 | Environment label is environment-managed |
| Experience | Localization | `/admin/settings/localization` | 9 | Default/fallback locale environment-managed |
| Security and Privacy | Security | `/admin/settings/security` | 6 | Yes |
| Security and Privacy | Authentication | `/admin/settings/authentication` | 16 | Yes |
| Engagement | Notifications | `/admin/settings/notifications` | 17 | Critical security alerts protected |
| Platform | Email | `/admin/settings/email` | 8 | Provider secrets environment-managed |
| Platform | Integrations | `/admin/settings/integrations` | 6 | Provider credentials not exposed |
| Security and Privacy | Privacy and Retention | `/admin/settings/privacy` | 6 | Retention and consent controls protected |
| Experience | Content and Publishing | `/admin/settings/content` | 9 | Workflow-bypass controls protected |
| Experience | SEO and Social Sharing | `/admin/settings/seo` | 7 | Robots can be environment-managed |
| Engagement | Engagement Forms | `/admin/settings/engagement` | 9 | Upload limits affect security |
| Platform | Media and Uploads | `/admin/settings/media` | 6 | Upload security and storage status protected |
| Platform | Search | `/admin/settings/search` | 5 | Privacy-safe telemetry only |
| Platform | Performance and Cache | `/admin/settings/performance` | 6 | Cache effects labelled |
| Operations | Maintenance | `/admin/settings/maintenance` | 6 | Recent MFA and reason required |
| Operations | Feature Flags | `/admin/settings/features` | 5 | High-risk flags require MFA/reason |
| Operations | Environment | `/admin/settings/environment` | Read-only | Environment status only |
