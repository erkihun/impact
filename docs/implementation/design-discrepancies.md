# Design discrepancies and resolutions

| ID | Source conflict or issue | Working resolution | Status |
|---|---|---|---|
| DD-001 | SRS content workflow includes `Unpublished`; LLD enum omits it | Preserve `unpublished` because unpublish is a mandatory public-content operation | Accepted implementation assumption |
| DD-002 | SRS lead statuses and LLD submission statuses use different vocabularies | Use the LLD operational sequence and retain terminal/qualification values as a superset | Product-owner confirmation required |
| DD-003 | Several LLD generated state/action tables map unrelated actions to modules | Treat prose requirements and domain invariants as authoritative over invalid generated mappings | Confirmation required |
| DD-004 | LLD persistence mappings reference unrelated table names for search/RBAC | Use normalized tables described elsewhere in the LLD schema catalogue | Confirmation required |
| DD-005 | LLD specifies MySQL `BINARY(16)` UUIDv7 while Laravel relations and route binding natively use canonical UUID strings | Current migrations store canonical UUIDv7 strings; a `BinaryUuid` cast exists but is not activated | Open architecture decision before production data |
| DD-006 | Baseline requires PHP 8.4; workstation provides PHP 8.2.12 | Enforce 8.4 in Composer, keep locally tested source 8.2-parseable, certify in PHP 8.4 CI | Open environment blocker |
| DD-007 | MFA is mandatory but no provider/recovery-code UX is fully specified | Implement RFC 6238 TOTP with encrypted secret and hashed one-use recovery codes using `pragmarx/google2fa` | Implemented assumption; security review pending |

No discrepancy is silently resolved when it changes user-visible workflow, security or persistence guarantees.
