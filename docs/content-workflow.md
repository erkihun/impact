# Content workflow

Supported states:

`draft → in_review → approved → scheduled/published → unpublished/archived`

Review can request changes; changed content returns to review. Archived content is terminal. The Action locks the content/version rows, authorizes the actor, validates the transition, records a workflow event and appends audit evidence in one transaction.

Self-approval prevention is configurable and enabled by default. Publication is locale-specific; a translation is not public merely because another locale is published.
