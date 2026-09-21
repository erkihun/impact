# Accessibility audit

Date: 2026-07-26

Target: WCAG 2.2 AA. This is implementation and browser evidence, not an independent conformance certification.

## Implemented evidence

| Area | Evidence | Result |
|---|---|---|
| Landmarks | skip links, banner/navigation/main/contentinfo on public pages; named main and navigation in admin/auth | Pass in sampled snapshots |
| Headings | one visible H1 per sampled page; section headings descend coherently | Pass |
| Keyboard focus | shared high-contrast `:focus-visible`; step headings accept programmatic focus | Pass in sampled flows |
| Desktop menus | button triggers, expanded state, Escape/outside close, focus restoration | Pass |
| Mobile dialogs | `role=dialog`, `aria-modal`, accessible title, initial close focus, focus trap, Escape and body scroll lock | Pass for public and admin drawers |
| Forms | visible labels, required text, help text, native types/autocomplete/length, field errors and focused error summary | Pass by source/test; representative browser flow sampled |
| Multi-step forms | native validity before advancing, no forward-step bypass, previous-step editing, live review | Pass |
| Language | `html lang=en/am`, equivalent-page switching, Noto Sans Ethiopic | Pass |
| Reflow | measured document overflow is 0 px at 1440, 1024, 768 and 390 | Pass for sampled screens |
| Reduced motion | `prefers-reduced-motion` disables nonessential transitions/animation | Pass by source |
| Status | success/status regions use status/live semantics; error summaries use alert semantics | Pass by source |
| Error pages | localized recovery text, no stack or vendor path leakage | Pass in automated tests |

## Keyboard evidence

1. Services mega menu opened by trigger; Escape set `aria-expanded=false` and returned focus to Services.
2. Public mobile sheet initially focused Close. `Shift+Tab` wrapped to the final Contact link. Escape returned focus to Open menu and restored body scrolling.
3. Admin mobile drawer opened as a named dialog and returned focus to Open menu on Escape.
4. Consultation Continue with an empty required challenge did not advance. After valid input, the Organization step opened and its H2 received focus.
5. RFP progress controls at 390 px expose names such as `1 Need`, `2 Organization`, `3 Project`, `4 Review`; labels are visually compact but not removed from assistive technology.

## Color and content safety

- Core colors are centralized and the high-contrast navy/white, teal/white and ink/quiet combinations are used for primary text and controls.
- Danger is reserved for actual destructive/error meaning; gold is not used as the sole status indicator.
- File selection copy explicitly says that selection is not approval and that files remain unavailable during security processing.
- No fabricated accessibility certification, customer logo, uptime or success metric is displayed.

## Remaining certification work

- Automated axe-core scan across a production-like representative route matrix.
- Measured contrast review of every derived shade and state.
- 200%/400% zoom, text-spacing override and forced-colors testing.
- NVDA/JAWS/VoiceOver task testing, including tables and all CMS forms.
- Firefox and WebKit keyboard/assistive-technology behavior.
- Captions/transcripts and editorial alternative-text quality after real media is loaded.
- Independent Amharic accessibility/content review.

Disposition: no blocking keyboard, landmark, labeling or reflow defect was found in the sampled implementation. WCAG 2.2 AA certification is not claimed.
