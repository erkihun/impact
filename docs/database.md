# Database

Run migrations with:

```powershell
php artisan migrate
```

Domain identifiers are UUIDv7. Foreign keys restrict destructive deletes unless nulling is explicitly safe. Version records are immutable in workflow intent; append-only audit and status histories preserve evidence. Sensitive intake values use Laravel encrypted casts.

The target design requests `BINARY(16)` UUID storage. Current migrations use canonical UUID strings while DD-005 remains open. Do not convert an existing database without a rehearsed, reversible migration and relation/route-binding tests.
