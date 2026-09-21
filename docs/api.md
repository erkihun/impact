# API

JSON endpoints are namespaced under `/api/v1`. The current public boundary exposes locale-specific search suggestions with throttling. Future administrative and integration endpoints must use Eloquent Resources, explicit authentication, permission policies, idempotency for writes, pagination and correlation IDs.

Do not expose encrypted intake fields, internal audit metadata, private file locations or unapproved draft content. An OpenAPI contract and complete error schema are required before external consumer onboarding.
