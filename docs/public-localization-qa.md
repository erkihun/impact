# Public Localization QA

## English and Amharic contract

- Route locale is set by the existing locale middleware.
- Public metadata, navigation, headings, actions, forms, errors, legal pages, consent, and recovery states use translation keys.
- The automated literal-key test confirms every literal Blade translation key has an Amharic entry.
- Missing settings diagnostics, status, search recovery, and About-page keys were added during this implementation.

## Ethiopic typography

Amharic renders with the self-hosted `Abyssinica SIL` WOFF2 asset, followed by `Noto Sans Ethiopic`, Arial, and sans-serif fallbacks. Browser verification at 390 px reported:

`"Abyssinica SIL", "Noto Sans Ethiopic", Arial, sans-serif`

Amharic headings use normal tracking and increased line height to avoid clipped or overly compressed Ethiopic text.

## Content behavior

- Dates in updated event and vacancy views use locale-aware translated formatting.
- Language switching preserves the current route when an equivalent URL exists.
- The UI does not translate administrator-authored identity/content values automatically. English organization/address values shown on Amharic pages are content/settings dependencies.
- Missing Amharic published records correctly produce data-driven empty states rather than copied English records.

## Verification

- `lang/am.json` parses successfully.
- localization feature tests pass;
- literal Blade translation coverage passes;
- Amharic home: status 200, `lang="am"`, one H1, no overflow;
- no mojibake was observed in the rendered browser output.

