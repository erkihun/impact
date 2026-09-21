# Admin Responsive QA

Viewports exercised by the screenshot helper:

- 390 x 900
- 768 x 1100
- 1280 x 1000
- 1920 x 1080

Requested but not completed in the helper:

- 375 px
- 430 px
- 1024 px
- 1440 px

Results:

- No horizontal overflow in the captured pass.
- Desktop captures reported exactly one H1.
- Mobile drawer markup exists with `role="dialog"` and `aria-modal="true"`.
- Admin table source uses stacked mobile record rows through `x-admin.record-list`.
- Sidebar collapse state now controls desktop sidebar width and content offset.

Limitation:

- Captured admin routes redirected to login because authenticated browser login was blocked by local credential drift. Responsive behavior for authenticated pages is supported by source structure and feature tests, but screenshots should be rerun after credential reset.
- The helper captured 390, 768, 1280, and 1920 px widths. The requested 375, 430, 1024, and 1440 px widths remain to be added to the helper.
