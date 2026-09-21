# Admin Accessibility QA

## Implemented Checks

- Skip link to `#admin-main-content`.
- One shared admin shell.
- Sidebar has `aria-label`.
- Active sidebar item uses `aria-current="page"` and a visible marker, not color alone.
- Mobile sidebar is a dialog with `aria-modal="true"`.
- Drawer has Escape handling, focus trap, focus restoration, and body-scroll lock in Alpine.
- Focus ring is tokenized with Knowledge Blue.
- Buttons keep practical 44 px targets through shared classes.
- Status badges include text plus a glyph and semantic color.
- Admin records use semantic tables on desktop and labelled stacked cells on mobile.
- Forms keep visible labels and error summary support.
- Reduced-motion CSS is present.
- HTML language is driven by the active Laravel locale.

## Automated Evidence

- `php artisan test tests\Feature\AdminUiConsistencyTest.php`: 3 passed, 72 assertions.
- `php artisan test tests\Feature\UiUxExperienceTest.php --filter="Amharic translation"`: passed.
- Browser screenshot helper: no horizontal overflow and one H1 in desktop captures.

## Not Completed

- Axe checks are not configured.
- Lighthouse checks are not configured.
- Manual screen-reader audit was not performed.
- Authenticated visual keyboard traversal should be repeated after credential reset.
