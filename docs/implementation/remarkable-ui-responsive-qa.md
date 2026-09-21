# Remarkable UI responsive QA

Date: 2026-07-26

## Required width matrix

| Width | Surface | Locale | Horizontal overflow | Result |
| ---: | --- | --- | ---: | --- |
| 375 | Homepage and admin content | English | 0px | Pass |
| 390 | Homepage | Amharic | 0px | Pass |
| 430 | Homepage | English | 0px | Pass |
| 768 | Consultation | English | 0px | Pass after context-panel contrast fix |
| 1024 | Service detail | English | 0px | Pass after context-panel contrast fix |
| 1280 | Services collection | English | 0px | Pass |
| 1440 | Homepage, admin register/editor/workflow | English | 0px | Pass |
| 1920 | Homepage | English | 0px | Pass |

## Behaviors checked

- Desktop mega navigation yields to a labelled mobile dialog.
- Hero columns stack without reordering the evidence narrative.
- Impact Index moves from four columns to a readable vertical sequence.
- Capability, sector, expert, and knowledge layouts retain hierarchy when stacked.
- Form progress labels remain usable at tablet widths.
- Form context rails follow the task canvas below the desktop breakpoint.
- Admin sidebar becomes a mobile navigation trigger.
- Admin filters, record actions, workflow panels, and editor controls stack without tables or horizontal scrolling.
- Amharic headings wrap within the viewport and do not force horizontal scroll.

## Evidence

Full-page artifacts are in `output/playwright/remarkable-ui`. DOM measurements were taken from the real browser by comparing `documentElement.scrollWidth` and `clientWidth`.
