# Search

Public search reads a locale-specific projection table through the `SearchIndexer` contract. The database implementation provides deterministic title/prefix ranking and the test fallback; Scout with the approved external engine is the production target.

Publication and archival workflows synchronize the projection after the database transaction commits. Only the published version is indexed; archived or unpublished content is removed. Search evidence stores an HMAC query hash and result count, never the raw query.

Production acceptance requires selecting and configuring the Scout engine, rebuilding its index from approved content, and verifying locale ranking, failover, latency, and deletion propagation.
