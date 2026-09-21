<?php

declare(strict_types=1);

namespace App\Data\Newsletter;

final readonly class SubscribeNewsletterData
{
    public function __construct(
        public string $email,
        public string $locale,
        public string $policyVersion,
        public string $ipHash,
        public string $correlationId,
        public string $source = 'public.footer',
    ) {}
}
