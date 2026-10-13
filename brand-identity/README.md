# TVET Bureau — Brand Identity Proposal

Four original logo concepts, colour system, typography, applications and usage guidelines for the **Addis Ababa City Administration Technical and Vocational Education and Training Bureau**, delivered as an interactive presentation plus production-ready vector files.

> This project lives in `brand-identity/` and is independent of the Laravel application in the repository root.

## Preview (no build needed)

**Run every npm command from inside `brand-identity/`** (the repository root has the Laravel `package.json`). All generated files are committed, so you can simply open `index.html` in a browser (works from `file://`), or run a local server:

```bash
cd brand-identity
npm install        # only needed to rebuild
npm run serve      # → http://localhost:4173
```

The presentation has: four concept cards with symbolism, a studio (concept × layout × variant × background, with a contrast warning), a variant gallery, palette with live contrast ratios, typography, 512 / 128 / 64 / 48 / 32 / 24 / 16 px readability tests, letterhead and ID-card mockups, a comparison and selection tool (your choice is remembered and drives the mockups), and the usage guidelines including clear-space, minimum-size and incorrect-usage diagrams.

## Downloads and export

Every **Download** button produces a real file:

| Action | Result |
|---|---|
| Download SVG | The exact SVG from `assets/` (byte-identical to the committed file) |
| PNG 512 / 1,024 / 2,048 / 4,096 px | Transparent PNG rendered in-browser at that pixel size on the **longest edge** |
| Copy SVG code | SVG markup to the clipboard |

PNG export draws the SVG into a canvas at the target size and never paints a background, so the alpha channel is preserved. 4,096 px renders a 16.7 MP canvas; very old or memory-constrained browsers may refuse, in which case the SVG is the fallback.

## Project layout

```
brand-identity/
├── index.html                 Interactive presentation (Tailwind CSS + vanilla JS)
├── BRAND_GUIDELINES.md        Full brand guide
├── assets/
│   ├── logos/                 12 SVG: 4 concepts × emblem / horizontal / vertical (full colour)
│   ├── variants/<concept>/    72 SVG: every layout in full-color, mono-blue, mono-black,
│   │                          grayscale, reversed-white, reversed-color
│   ├── exports/               28 transparent PNGs (1024 / 2048 / 4096 px emblems, lock-ups)
│   ├── fonts/                 Inter + Noto Sans Ethiopic (woff2, SIL OFL 1.1)
│   ├── css/site.css           Compiled Tailwind
│   └── js/                    app.js (UI + export) · brand-data.js (generated geometry)
├── src/                       Build, export and verification scripts
│   ├── logos.mjs              ★ The four emblems as role-tagged vector paths (edit here)
│   ├── palette.mjs            Brand colours and variant mapping
│   ├── copy.mjs               Exact institutional titles
│   ├── layouts.mjs, text.mjs  Lock-ups; text → outlines via opentype.js
│   ├── render.mjs             Single SVG composer (shared by files and the page)
│   ├── build-assets.mjs       Writes assets/logos, assets/variants, assets/js/brand-data.js
│   ├── export-png.mjs         Renders assets/exports using the page's own exporter
│   └── verify.mjs             End-to-end checks (see below)
└── tailwind.config.cjs
```

## Rebuilding

```bash
npm run build       # SVG files + brand-data.js + Tailwind CSS
npm run export:png  # PNGs (needs Chromium; set CHROMIUM_PATH if it is not auto-detected)
npm run verify      # full verification
```

`export:png` and `verify` use `playwright-core` with an installed Chrome, Edge or Chromium (auto-detected on Linux, macOS and Windows, or set `CHROMIUM_PATH`); no browser is downloaded.

To change a logo, edit its function in `src/logos.mjs`, then `npm run build && npm run export:png && npm run verify`. Each shape has a `role` (`primary`, `secondary`, `accent`, `neutral`) and every variant is produced by re-mapping roles to colours, so new concepts get all variants automatically.

## What `npm run verify` checks

- Exact Amharic and English institutional strings; every glyph present in the fonts used
- All 84 SVG files parse as XML and contain only vector paths (no raster, live text, filters, gradients, masks)
- No two shapes inside any emblem overlap (guarantees lossless one-colour output)
- Fonts load; no console errors; no horizontal scroll at 375, 768 and 1440 px
- Clicking the Download buttons yields the correct SVG and PNGs at 1024, 2048 and 4096 px, RGBA with transparent corners; horizontal lock-up exports too
- All committed PNGs: correct size, RGBA, transparent corners

## SVG notes (Illustrator / Inkscape)

Files use only `<svg>`, `<title>`, `<desc>`, `<g>` and `<path>` with absolute commands, hex `fill` attributes and no CSS, filters, masks or embedded fonts — the most conservative subset for Adobe Illustrator, Inkscape and Affinity. Layers are named (`emblem`, `wordmark`, `gear`, `spark`…). Lock-up text is outlined. This subset was verified to parse and render in Chromium; it has **not** been opened in Illustrator or Inkscape in this environment.

## Licences

Inter and Noto Sans Ethiopic: SIL Open Font License 1.1 (`assets/fonts/LICENSE-*.txt`). The logo artwork is original, drawn from geometric primitives, and uses no stock icons.
