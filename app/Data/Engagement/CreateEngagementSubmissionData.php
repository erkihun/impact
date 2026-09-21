<?php

declare(strict_types=1);

namespace App\Data\Engagement;

use App\Enums\SubmissionType;
use Illuminate\Http\UploadedFile;

final readonly class CreateEngagementSubmissionData
{
    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function __construct(
        public SubmissionType $type,
        public string $locale,
        public string $contactName,
        public string $email,
        public string $description,
        public string $correlationId,
        public string $policyVersion,
        public ?string $organizationName = null,
        public ?string $role = null,
        public ?string $phone = null,
        public ?string $serviceId = null,
        public ?string $industryId = null,
        public ?string $timeframe = null,
        public ?string $budgetRange = null,
        public ?string $idempotencyKey = null,
        public ?string $ipHash = null,
        public array $attachments = [],
    ) {}
}
