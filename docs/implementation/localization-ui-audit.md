# Localization UI audit

Date: 2026-07-26

Locales: English (`en`) and Amharic (`am`).

## Automated evidence

- Blade literal translation keys scanned: 485.
- Keys missing from `lang/am.json`: 0.
- Regression: `UiUxExperienceTest` parses all Blade views and fails when a literal `__()` key is absent.
- Amharic catalog parses as valid JSON with 591 entries.
- Equivalent-page locale links preserve the route path, for example `/en/request-for-proposal` → `/am/request-for-proposal`.

## Browser evidence

- `/am` rendered with `html lang="am"`.
- Computed H1 font stack begins with `"Noto Sans Ethiopic"`.
- No horizontal overflow at 390 px.
- No browser console errors or warnings in the sampled Amharic home flow.
- Desktop and mobile Amharic screenshots are stored as `home-am-1440.png` and `home-am-390.png`.

## Display contract

- UI chrome, navigation, calls to action, form labels/help, task steps, errors and recovery pages are localized through Laravel translation keys.
- Dynamic content is selected by locale at the query/controller layer. The UI does not silently relabel English dynamic content as Amharic.
- Some non-production seeded Amharic records have English summary bodies or lack equivalent case/expert/insight versions. That is fixture/content completeness, not a missing UI translation key; the page omits absent records rather than inventing translations.
- Dates shown by current views use application-provided localized formatting where available. No alternate-calendar storage contract was introduced.

## Remaining acceptance

- Professional review of all Amharic copy for consulting terminology and tone.
- Production-content parity across every service, industry, expert, case study, insight, event and vacancy.
- Longest-string and real-data checks for admin tables, email templates and validation messages.
- Locale-specific date/time conventions require an approved product decision where current models only expose Gregorian timestamps.

Disposition: static UI translation coverage is complete for the current Blade surface. Full bilingual editorial-content parity depends on approved content records.
