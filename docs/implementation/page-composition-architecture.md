# Page composition architecture

## Request flow

1. Laravel resolves locale and settings.
2. The public view composer maps the current route name to a page key.
3. `PageComposer::published()` resolves and caches the latest active published composition.
4. Current immutable section versions are loaded with typed relations, media, and actions.
5. Schedule and enabled-state filters run before rendering.
6. `x-ui.page-composition` maps controlled presentation enums to approved design-system classes and includes the registered renderer.

## Persistence

- `page_templates`: approved template definitions.
- `page_compositions`: localized immutable page-version lineage and workflow state.
- `page_sections`: stable ordered slots inside a composition.
- `page_section_versions`: immutable structured content and presentation payloads.
- `page_section_relations`: explicit typed content selections.
- `page_section_media`: approved library-media usage, focal data, decorative decision, caption.
- `page_section_actions`: internal/external CTA destinations and controlled button variants.
- `page_composition_workflow_events`: state transition evidence.
- `page_navigation_configurations`: localized primary/footer link configuration with version and lock counters.

All identities use the application UUIDv7 model concern. Foreign keys restrict unsafe deletion and preserve composition lineage through `based_on_id`.

## Mutation rules

`PageCompositionMutator` owns add, update, reorder, duplicate, archive, and restore. It:

- authorizes through the policy;
- locks the composition row;
- compares `lock_version`;
- rejects non-editable states;
- validates against `PageSectionRegistry`;
- creates an immutable section version;
- preserves typed relations/actions/media unless explicitly replaced;
- increments the composition lock/hash;
- records audit evidence.

Published versions cannot be edited. `CreatePageCompositionDraftAction` clones the composition and all current section associations into the next version. Workflow moves draft → review → approved → scheduled/published, with permission separation and atomic replacement of the previous published version.

## Cache and preview

Published pages use locale/page-key cache keys. Page publication and navigation updates invalidate affected keys. Preview routes are authenticated, permission checked, temporary-signed, expiring, and rendered with `noindex,nofollow,noarchive`.
