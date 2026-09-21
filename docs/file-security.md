# File security

The implemented lifecycle is `received -> quarantined -> scanning -> clean/rejected -> processing -> ready -> approved`.

- `ClamAvMalwareScanner` streams objects from any configured filesystem disk to a securely created temporary file, scans them, and removes the temporary copy.
- `ProcessMediaAssetJob` strips image metadata and creates responsive WebP variants.
- `ApproveMediaAction` permits only clean, ready assets, promotes them to the public or private disk, records the audit event, and removes quarantine objects after a successful copy.
- Confidential media and recruitment files use authorization plus expiring signed routes.

`MediaLifecycleTest` verifies processing, invalid-state rejection, promotion, and signed delivery. Production enablement still requires live S3-compatible quarantine/public/private buckets, bucket policies and lifecycle rules, a reachable ClamAV daemon, and an end-to-end clean/malicious-file acceptance run.
