# Permissions

Permission codes are centralized in `App\Support\PermissionCatalog`; system role mappings are in `RolePermissionMap` and seeded idempotently.

Authorization is applied twice where relevant:

- route/controller policy checks protect direct requests;
- mutating Actions perform a final policy or permission check;
- UI visibility reflects the same permission but is not a security boundary.

Privileged roles additionally pass verified-email, current-session and MFA middleware. Any new admin route must add a direct-access feature test for allowed and denied roles.
