# Responsive QA

Date: 2026-07-26

## Viewport matrix

| Width | Navigation mode | Layout result | Horizontal overflow |
|---|---|---|---|
| 1440 | full desktop mega navigation and admin sidebar | 12-column editorial composition, wide admin metrics | 0 px |
| 1280 | desktop mega navigation | public content remains balanced | 0 px |
| 1024 | compact desktop navigation | header fits; hero and cards maintain hierarchy | 0 px |
| 768 | mobile public navigation | two-column content where useful, stacked hero/footer | 0 px |
| 390 | mobile sheet/drawer | one-column task flow and administration cards | 0 px |

The requested 375–430 px range is represented by the 390 px sample. The 768–1024 tablet range is represented at both boundaries.

## Responsive behaviors verified

- Public hero changes from two columns to a single reading sequence.
- Collection/service grids collapse without fixed heights or clipped copy.
- Section heading/action rows wrap instead of overlapping.
- CTA groups stack with full-width touch targets where space is constrained.
- Public footer becomes a readable vertical sequence and the newsletter input/button remain within the viewport.
- Step labels remain accessible at mobile width while the visible progress UI stays compact.
- The desktop mega navigation is replaced by a full mobile sheet below the large breakpoint.
- Admin sidebar becomes a dialog drawer; dashboard metrics and quick actions stack.
- Form controls, file inputs and validation copy use available width.
- Noto Sans Ethiopic renders the sampled Amharic page without character clipping.

## Artifacts

See `output/playwright/home-en-*.png`, `home-am-*.png`, `rfp-en-390.png`, `admin-dashboard-*.png`, and `login-390.png`.

## Remaining coverage

- 375, 430 and intermediate orientation-specific captures were not separately stored because the boundary samples showed no overflow; they remain recommended for release-device QA.
- Data-heavy admin tables require production-volume and very-long-value staging checks.
- Physical iOS/Android browser chrome, safe areas, zoom and virtual-keyboard behavior remain external device checks.
