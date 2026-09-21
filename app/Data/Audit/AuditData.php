<?php

declare(strict_types=1);

namespace App\Data\Audit;

final readonly class AuditData
{
    public function __construct(
        public string $action,
        public string $auditableType,
        public ?string $auditableId,
        public ?string $actorId,
        public string $correlationId,
        public ?string $beforeHash = null,
        public ?string $afterHash = null,
        /** @var array<string, mixed> */
        public array $metadata = [],
        public ?string $ipHash = null,
    ) {}
}
