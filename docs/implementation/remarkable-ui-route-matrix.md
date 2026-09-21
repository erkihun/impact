# Remarkable UI route matrix

Date: 2026-07-26

The application exposes 90 non-vendor routes. This matrix records the visual system applied to each screen family; route names, middleware, controllers, validation, and authorization remain intact.

| Route family | Representative routes | Experience |
| --- | --- | --- |
| Home | `/`, `/{locale}` | Immersive evidence-led hero, Impact Index, capability architecture, transformation record, sector intelligence, expert perspective, knowledge publication, engagement pathway |
| About | `/{locale}/about` | Standard institutional header and long-form editorial content |
| Services | `/{locale}/services`, `/{locale}/services/{slug}` | Editorial register; numbered challenge, approach, deliverables, and value detail |
| Industries | `/{locale}/industries`, `/{locale}/industries/{slug}` | Sector register; contextual overview and challenges |
| Experts | `/{locale}/experts`, `/{locale}/experts/{slug}` | Paper header, named profile emphasis, biography and qualifications |
| Case studies | `/{locale}/case-studies`, `/{locale}/case-studies/{slug}` | Transformation register; numbered challenge, approach, and authorized outcomes |
| Insights | `/{locale}/insights`, `/{locale}/insights/{slug}` | Knowledge header and publication-led reading layout |
| Events | `/{locale}/events`, `/{locale}/events/{slug}` | Editorial collection/detail with real dates and registration action |
| Careers | `/{locale}/careers`, `/{locale}/careers/{slug}` | Editorial collection/detail and existing application workflow |
| Search | `/{locale}/search`, `/api/v1/search/suggestions` | Task-oriented search and private suggestion API |
| Consultation | `/{locale}/consultation`, `/{locale}/consultation-requests` | Four-step decision-led intake plus context rail |
| RFP | `/{locale}/request-for-proposal`, `/{locale}/rfp-requests` | Four-step secure proposal intake plus file-handling rail |
| Contact/newsletter | `/{locale}/contact`, `/{locale}/newsletter-subscriptions`, lifecycle links | Existing consent-aware actions in the public system |
| Authentication | `/login`, password, email verification, invitation, MFA routes | Split protected-workspace shell and focused authentication cards |
| Admin dashboard | `/admin` | Operational overview within the stable admin shell |
| Admin content | `/admin/content*` | Editorial register, immutable editor, preview, workflow, rollback, revision history |
| Admin engagement | `/admin/engagement*` | Existing lifecycle operations in the admin shell |
| Admin applications | `/admin/applications*` | Existing recruitment operations in the admin shell |
| Admin media | `/admin/media*`, restricted downloads | Existing secure media lifecycle and private download actions |
| Admin identity | `/admin/users*`, `/admin/roles*` | Permission-aware identity operations |
| Admin assurance | `/admin/audit-events`, `/admin/settings` | Audit and typed settings workspaces |
| Profile/security | `/profile`, `/dashboard`, `/mfa*`, `/logout` | Authenticated identity and security surfaces |
| Platform/SEO | `/ready`, `robots.txt`, `sitemap.xml`, locale sitemaps, fallback redirect | No visual behavior change; verified by existing tests |

## Language handling

All localized public route families were reviewed in English and Amharic. Content availability remains truthful per locale; absent published records are not fabricated to create visual symmetry.
