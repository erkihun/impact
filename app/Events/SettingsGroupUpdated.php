<?php

declare(strict_types=1);

namespace App\Events;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SettingsGroupUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  list<string>  $keys
     * @param  list<string>  $effects
     */
    public function __construct(
        public readonly string $category,
        public readonly array $keys,
        public readonly array $effects,
        public readonly string $actorId,
        public readonly CarbonImmutable $occurredAt,
    ) {}
}
