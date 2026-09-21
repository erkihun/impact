<?php

declare(strict_types=1);

namespace App\Data\Privacy;

final readonly class RecordConsentData
{
    /**
     * @param  array<string, bool>  $decisions
     */
    public function __construct(
        public array $decisions,
        public string $policyVersion,
        public string $subjectKeyHash,
        public string $ipHash,
        public string $correlationId,
    ) {}
}
