# Public content control matrix

| Public element | Control source | Validation/versioning | Render owner |
|---|---|---|---|
| H1, eyebrow, summary | page section version | registry + immutable version | registered section Blade |
| Section order/enabled | composition/section | optimistic lock + audit | page composition component |
| Surface/width/spacing | presentation enums | enum allow-list | design-system class map |
| Public media | media library relation | clean + ready + public + alt decision | media partial |
| CTA | section action | route existence/HTTPS + variant enum | action partial |
| Related records | section relation | typed morph + bounded count | collection partial |
| Catalog records | localized domain versions | existing publication scopes/workflow | existing collection/detail bodies |
| Form fields | source-controlled fixed schema | Form Request + throttling + consent | secure form views |
| Primary labels | navigation registry | route preserved + lock/version | public layout |
| Footer links | navigation registry | route preserved + lock/version | public layout |
| Branding/contact/SEO defaults | typed settings registry | settings validation/audit | public layout |
| Locale typography | appearance/localization settings | approved font enum | public body classes |

No editor field accepts CSS, JavaScript, Blade, SQL, route definitions, storage paths, or arbitrary HTML. Rich text is emitted as escaped text with preserved line breaks.
