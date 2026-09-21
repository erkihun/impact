<?php

declare(strict_types=1);

namespace App\Data\Recruitment;

final readonly class SubmitApplicationData
{
    public function __construct(
        public string $vacancyId,
        public string $applicantName,
        public string $email,
        public string $mediaAssetId,
        public string $policyVersion,
        public string $correlationId,
        public string $ipHash,
        public ?string $phone = null,
        public ?string $coverLetter = null,
    ) {}
}
