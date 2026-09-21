# Architecture

The platform is a Laravel 12 modular monolith with three delivery boundaries:

1. localized public Blade pages and forms;
2. authenticated administration routes under `/admin`;
3. versioned JSON endpoints under `/api/v1`.

Controllers delegate business work to Actions. Eloquent parents hold stable identity; locale/version tables hold publishable copy. MySQL is authoritative, Redis supports cache/queue/session, Scout targets Meilisearch, and private object storage holds files. Correlation IDs flow through requests, audit events and jobs.

See `implementation/architecture-decisions.md` and `implementation/design-discrepancies.md`.
