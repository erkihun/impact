# Page section registry

The registry is the only source of allowed public-page components. Unknown types, fields, presentation properties, and variants fail validation.

| Type | Purpose | Variants | Special data |
|---|---|---|---|
| `homepage_hero` | Home value proposition | default, split media | approved image, actions, evidence |
| `page_header` | Semantic H1 introduction | standard, paper, knowledge, media | optional approved image |
| `rich_text` | Escaped editorial copy | reading, editorial | no arbitrary HTML |
| `image_text` | Media/editorial pairing | split, stacked | approved image |
| `impact_metrics` | Bounded evidence list | index, grid | structured items |
| `featured_collection` | Selected published records | editorial, grid | manual/automatic mode |
| `related_content` | Related records | editorial, compact | explicit relations |
| `quote` | Attributed quotation | editorial, brand | quote, attribution, role |
| `faq` | Accessible disclosure list | default | structured Q&A items |
| `cta_panel` | Engagement prompt | brand, quiet, dark | typed actions |
| `contact_panel` | Contact pathway | default, split | office relation |
| `newsletter_panel` | Newsletter context | default, compact | fixed secure form remains code-owned |
| `form_introduction` | Secure-form guidance | standard, compact | fixed form schema remains code-owned |
| `divider` | Approved separation | line, space | no arbitrary size |

Presentation properties are enums for surface tone, container width, alignment, media position, spacing, card variant, and mobile stacking. The public component converts these values to a fixed Tailwind class map.

Media is selected only from public, malware-clean, fully processed `MediaAsset` records. Non-decorative public media must have alternative text. URL entry is not exposed in the editor.
