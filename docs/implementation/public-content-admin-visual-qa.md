# Public content admin visual QA

Date: 2026-07-29  
Method: Playwright CLI against `http://127.0.0.1:8018`; in-app browser was unavailable.

## Results

| Surface | Viewport | Result |
|---|---:|---|
| Public home EN | 1440×1000 | one H1; managed hero visible; Home/nav icons visible; no horizontal overflow |
| Public home AM | 390×844 | one H1; no horizontal overflow; computed H1 font includes `Abyssinica SIL` |
| Page composer published | 1440×1000 | three columns resolve to approximately 272/457/304 px; published controls disabled; no horizontal overflow |
| Draft creation flow | 1440×1000 | published record cloned to v2 draft; editor controls enabled; success status announced |

Artifacts:

- `output/playwright/public-home-desktop.png`
- `output/playwright/public-home-am-mobile.png`
- `output/playwright/page-composer-desktop-final.png`

The initial page-composer check rendered stacked because the existing production asset bundle did not include new Tailwind classes. `npm run build` regenerated the manifest/CSS/JS; repeat inspection confirmed the three-panel grid.

Keyboard/semantic observations: skip links exist, public/admin pages expose one H1, details/FAQ use native disclosure semantics, published fieldsets are disabled, preview opens separately with `noopener`, and status messages use live-region roles supplied by the existing shell.
