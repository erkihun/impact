# Public Responsive QA

## Automated matrix

The browser matrix covered 26 routed states at each required width:

`375, 390, 430, 768, 1024, 1280, 1440, 1920`

Total: **208 route/viewport checks**.

Each check asserted:

- correct HTTP status (`200`, or `404` for the fallback);
- exactly one H1;
- `main#main-content` present;
- expected `lang="en"`;
- no images missing `alt`;
- no horizontal document overflow.

Final result: **208/208 passed**.

## Defect found and corrected

The first 375/390 pass found 36 px and 21 px of overflow on the vacancy page. The native CV input was the source. The shared file-upload control now stacks on narrow screens, constrains the native input, and hides internal overflow. Both widths passed on rerun.

## Interaction checks

- desktop About mega menu: `aria-expanded` changed `false → true → false` using click and Escape;
- mobile navigation: `aria-expanded` changed `false → true → false` using click and Escape;
- consultation form: continued to step 2 and returned to step 1 successfully;
- Amharic at 390 px: one H1, zero overflow, `lang="am"`, Abyssinica SIL computed.

## Visual evidence

Final images are in `output/playwright/public-ui-final/`, including home, About, search results, no results, event, vacancy, consultation, and 404 at 375, 768, 1440, and 1920 px, plus the Amharic home at 390 px.

