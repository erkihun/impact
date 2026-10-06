<?php

declare(strict_types=1);

namespace App\Data\Seo;

use App\Enums\Seo\SeoIssueSeverity;

final readonly class SeoIssue
{
    public function __construct(
        public string $code,
        public SeoIssueSeverity $severity,
        public string $message,
        public ?string $url = null,
        public ?string $subject = null,
    ) {}

    /** @return array{code: string, severity: string, message: string, url: string|null, subject: string|null} */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity->value,
            'message' => $this->message,
            'url' => $this->url,
            'subject' => $this->subject,
        ];
    }
}
