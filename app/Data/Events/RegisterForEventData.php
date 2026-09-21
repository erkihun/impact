<?php

declare(strict_types=1);

namespace App\Data\Events;

final readonly class RegisterForEventData
{
    public function __construct(
        public string $eventId,
        public string $name,
        public string $email,
        public string $locale,
        public string $policyVersion,
        public string $correlationId,
        public string $ipHash,
        public bool $marketingConsent = false,
    ) {}
}
