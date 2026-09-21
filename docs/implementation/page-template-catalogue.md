# Page template catalogue

| Template | Applied surfaces | Required structural section |
|---|---|---|
| Homepage | localized home | homepage hero |
| Institutional | About | page header |
| Landing/directory | services, industries, experts, case studies, insights, events, careers, search | page header |
| Detail | service, industry, expert, case-study details | page header with runtime title override |
| Article | insight detail | page header with runtime title override |
| Event | event detail | page header with runtime event context |
| Career | vacancy detail | page header with runtime vacancy context |
| Form | consultation, RFP, contact | form introduction around fixed form |
| Legal | privacy, terms, cookies, accessibility | page header plus legal body |
| Error | 404 and 500 | page header/error context |

Every published composition must contain exactly one enabled H1-producing section. Dynamic domain titles and summaries may override the generic detail header at render time; this preserves a managed layout while keeping current published domain data authoritative.

The bilingual seed creates 52 published compositions: 26 page keys × EN/AM. Rerunning the seeder is idempotent and never overwrites an existing editorial composition.
