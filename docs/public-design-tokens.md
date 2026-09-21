# Public Design Tokens

## Character

The public system is “Executive Editorial”: evidence-led, calm, precise, and premium without ornamental excess. Layouts favor editorial rules, numbered sequences, strong type hierarchy, deliberate whitespace, and meaningful dark or paper surfaces over generic card grids.

## Color roles

| Token | Default | Role |
|---|---:|---|
| `--color-primary-900` | `#17324d` | brand navy, primary editorial structure |
| `--color-primary-700` | `#2d6c99` | knowledge and informational emphasis |
| `--color-secondary-700` | `#2d7a78` | advisory actions and interactive emphasis |
| `--color-accent-600` | `#c99a2e` | selective gold markers and highlights |
| `--color-text-default` | `#1f2933` | primary text |
| `--color-text-muted` | `#5d6a74` | secondary text |
| `--color-surface-default` | `#ffffff` | default surface |
| `--color-surface-muted` | `#f2f4f6` | quiet surface |
| `--color-border-default` | `#cdd5dc` | standard separation |
| status tokens | catalogued in `app.css` | success, warning, danger, information |

Brand, border, surface, and card-radius values that are administrator-configurable are injected as resolved CSS variables. Templates do not contain settings-specific raw values.

## Typography

- English UI: the configured sans/editorial stack.
- Amharic UI: self-hosted `Abyssinica SIL`, then `Noto Sans Ethiopic`, Arial, sans-serif.
- Display headings use tight English tracking and normal Ethiopic tracking.
- Body copy targets readable line lengths with the `reading-width` utility.
- Eyebrows use compact uppercase English tracking; Amharic uses reduced tracking.

## Spacing and layout

- Base rhythm: 4 px multiples.
- Component gaps: 8, 12, 16, 20, 24, 32, 40, and 48 px.
- Public sections: typically 48–80 px vertical padding, increasing toward 96 px for major editorial transitions.
- Container: `content-container`, maximum 90 rem, responsive 16/32/48/64 px inline padding.
- Reading measure: approximately 70 characters.
- Touch targets: at least 44 px for buttons, links in navigation, and icon controls.

## Responsive breakpoints

The implementation follows Tailwind’s mobile-first breakpoints and is explicitly verified at 375, 390, 430, 768, 1024, 1280, 1440, and 1920 px.

- under 1024 px: mobile navigation sheet replaces desktop navigation;
- forms stack before two-column layouts;
- complex editorial rows collapse into linear reading order;
- sidebars become normal-flow panels;
- file inputs stack their icon and browser control on narrow screens.

## States

Every interactive primitive must preserve:

- default, hover, active, focus-visible, disabled, and invalid states;
- visible focus using `--focus-ring`;
- status meaning through text/icon plus color;
- reduced-motion behavior through media query and settings class;
- high-contrast enhancement through semantic token overrides.

