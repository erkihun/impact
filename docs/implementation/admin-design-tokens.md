# Admin Design Tokens

The admin UI now uses one semantic token layer in `resources/css/app.css`, backed by Tailwind mappings in `tailwind.config.js`.

## Approved Core Tokens

| Token | Value | Meaning |
| --- | --- | --- |
| `--color-primary-900` | `#17324d` | Brand Navy |
| `--color-primary-700` | `#2d6c99` | Knowledge Blue |
| `--color-secondary-700` | `#2d7a78` | Advisory Teal |
| `--color-accent-600` | `#c99a2e` | Selective Gold |
| `--color-text-default` | `#1f2933` | Primary text |
| `--color-text-muted` | `#5d6a74` | Muted text |
| `--color-surface-default` | `#ffffff` | Default surface |
| `--color-surface-muted` | `#f2f4f6` | Quiet surface |
| `--color-border-default` | `#cdd5dc` | Default border |
| `--color-success` | `#238636` | Success |
| `--color-warning` | `#b7791f` | Warning |
| `--color-danger` | `#c0392b` | Danger |
| `--color-info` | `#2d6c99` | Informational state |

## Derived States

Implemented derived tokens include sidebar hover/active, selected surface, table hover/selected, disabled surface, focus border, status backgrounds, overlay, skeleton, and bounded chart colors.

## Source Scan Result

Source scan for raw hex values in admin Blade found no raw hex values. Raw values are centralized in `resources/css/app.css` and `tailwind.config.js`.
