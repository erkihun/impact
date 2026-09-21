# Security

The foundation includes HTTPS enforcement, HSTS on secure responses, CSP and browser headers, verified email, privileged-route MFA assertion, permission/policy boundaries, encryption casts, throttling, consent evidence, UUID references and append-only audit events.

Before production:

- validate the implemented TOTP enrollment/challenge/recovery flow on PHP 8.4 and approve the operational recovery procedure;
- complete direct-URL matrices for the remaining administration modules;
- validate the implemented malware scanning and private signed delivery against live S3-compatible storage and ClamAV;
- configure trusted proxies, secure cookies and secret/KMS rotation;
- validate CSP, dependency inventory, SAST/DAST and penetration testing;
- enforce DB-level audit immutability or immutable export;
- approve retention/legal-hold jobs.

See `implementation/security-control-matrix.md`.
