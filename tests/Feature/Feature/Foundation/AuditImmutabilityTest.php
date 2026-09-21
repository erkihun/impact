<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use Illuminate\Support\Str;

it('prevents audit events from being updated or deleted', function (): void {
    $event = AuditEvent::query()->create([
        'action' => 'test.created',
        'correlation_id' => (string) Str::uuid7(),
        'metadata' => ['source' => 'test'],
    ]);

    expect(fn () => $event->update(['action' => 'test.changed']))
        ->toThrow(LogicException::class, 'append-only');

    expect(fn () => $event->delete())
        ->toThrow(LogicException::class, 'append-only');
});
