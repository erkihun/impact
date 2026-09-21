# Operations

Readiness is exposed at `/ready` and checks the database, cache and storage boundary. A healthy response must reflect dependencies, never a hard-coded label.

Operators monitor HTTP error rate/latency, queue depth/failures, database capacity, cache availability, storage/scanner health, mail delivery, search indexing delay, suspicious authentication and audit export. Correlation IDs are returned as `X-Correlation-ID`.

Incident response must preserve evidence, restrict access, rotate compromised secrets, communicate under the approved plan and document recovery actions.
