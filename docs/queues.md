# Queues

Production uses Redis-backed queues separated by concern: `default`, `media`, `search`, `notifications` and `publishing`. Media processing is unique and retry-bounded; acknowledgement notifications are queued after commit and localized to the submitter's locale.

Run locally:

```powershell
php artisan queue:work --tries=3
php artisan schedule:work
```

The repository includes `deploy/supervisor/impact-workers.conf` with separate worker groups. Live Redis connectivity, worker restart/reload, alert routing, dead-letter review, and a failed-job replay drill are production blockers.
