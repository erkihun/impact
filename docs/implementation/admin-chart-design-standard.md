# Admin Chart Design Standard

No live admin chart is currently rendered in the application. The dashboard uses operational queues and KPI cards backed by existing queries instead of decorative analytics.

When an operational chart is added, it must use:

- Primary series: `#2D6C99`
- Secondary series: `#2D7A78`
- Benchmark/reference: `#17324D`
- Selective highlight: `#C99A2E`
- Success: `#238636`
- Warning: `#B7791F`
- Danger: `#C0392B`
- Neutral comparison: `--chart-neutral`

Required structure:

- `x-admin.chart-card`
- `x-admin.chart-header`
- `x-admin.chart-toolbar`
- `x-admin.chart-range-control`
- `x-admin.chart-legend`
- `x-admin.chart-empty-state`
- `x-admin.chart-error-state`
- `x-admin.chart-data-summary`

Rules:

- Use no more than four meaningful series.
- Do not use rainbow palettes.
- Do not use red/green unless the data has true failure/success semantics.
- Include an accessible text summary or data-table equivalent.
- Respect reduced motion.
- Do not add charts only to fill dashboard space.
