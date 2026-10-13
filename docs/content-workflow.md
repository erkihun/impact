# Content workflow

Supported states:

`draft → published` (direct), or `draft → in_review → approved → scheduled/published → unpublished/archived`

Review is optional. Anyone who can create content (`content.create`) can publish or schedule it straight away: the create and edit forms offer a "Publish immediately" option, and the workflow panel defaults to "Publish now" from draft, in review and changes requested. When a team wants a second pair of eyes, the submit → approve → publish path still works and self-approval prevention still applies to the approval step.

When content is created or revised with "publish" set, creation and publication run in one database transaction. A publication check that fails (for example a missing default-language version or a blocking SEO issue) rolls the whole request back and is reported on the form, so no orphaned draft is left behind.

Review can request changes; changed content returns to review or is published directly. Archived content is terminal. The Action locks the content/version rows, authorizes the actor, validates the transition, records a workflow event and appends audit evidence in one transaction.

Self-approval prevention is configurable and enabled by default. Publication is locale-specific; a translation is not public merely because another locale is published.
