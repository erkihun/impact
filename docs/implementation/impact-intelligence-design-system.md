# Impact Intelligence design system

Date: 2026-07-26

## Design principles

1. Evidence before claims.
2. Editorial hierarchy before decoration.
3. Structured asymmetry before repeated cards.
4. Context beside action.
5. State and workflow must remain visible.
6. English and Amharic are independent reading experiences.

## Core visual language

| Element | Purpose | Implementation |
| --- | --- | --- |
| Impact Line | Connects evidence, sections, and decision paths | `x-ui.impact-line` |
| Impact Index | Shows real published-record counts with context | `x-ui.impact-index` |
| Insight Marker | Identifies analytical or editorial entry points | `x-ui.insight-marker` |
| Editorial Number | Gives sequences and records a stable visual rhythm | `x-ui.editorial-number` |
| Structured Geometry | Adds restrained architectural depth | `.impact-geometry` and `.impact-media-frame` |

## Color and surface model

- Deep navy: institutional structure and high-trust surfaces.
- Teal: action and operational movement.
- Knowledge blue: analysis, publication, and navigation signals.
- Gold: one proof point or editorial accent, not ambient decoration.
- Paper: long-form and publication surfaces.
- Quiet editorial gray: sector context and operational backgrounds.

No gradients, glassmorphism, neon effects, floating blobs, or decorative dashboard chrome are used.

## Typography

- Editorial display type is reserved for consequential headings, quotations, and publication titles.
- Interface sans-serif supports navigation, form labels, statuses, and operational content.
- Monospace is used narrowly for numbering, state context, and stable identifiers.
- Heading scale is fluid and bounded to prevent viewport overflow.

## Layout rules

- Public content uses a 90rem maximum container with responsive gutters.
- Major page headers support `standard`, `knowledge`, and `paper` variants.
- Lists become editorial registers instead of equal cards.
- Forms become a primary task canvas plus context panel at large widths.
- Admin pages use a stable sidebar/topbar, contextual header, primary work canvas, and attention rail.

## Interaction and accessibility rules

- Controls retain at least a 44px interactive target.
- Navigation dialogs trap focus, close on Escape, and return focus to the trigger.
- Hover is an enhancement; content and actions remain available without it.
- Focus-visible styles are globally defined.
- Reduced-motion preferences disable non-essential animation and smooth scrolling.
- Images use explicit dimensions and alt attributes; decorative art uses empty alt text.

## Primary source locations

- `resources/css/app.css`
- `resources/views/components/ui/impact-line.blade.php`
- `resources/views/components/ui/impact-index.blade.php`
- `resources/views/components/ui/insight-marker.blade.php`
- `resources/views/components/ui/editorial-number.blade.php`
- `resources/views/components/ui/page-header.blade.php`
- `public/images/impact-intelligence-hero-v1.webp`
