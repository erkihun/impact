<?php

declare(strict_types=1);

namespace App\Enums\Seo;

enum RobotsDirective: string
{
    case IndexFollow = 'index, follow';
    case NoindexFollow = 'noindex, follow';
    case NoindexNofollow = 'noindex, nofollow';

    public function isIndexable(): bool
    {
        return $this === self::IndexFollow;
    }

    /**
     * Accepts stored or legacy spellings such as "noindex,follow".
     */
    public static function parse(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $tokens = collect(explode(',', strtolower($value)))->map(static fn (string $token): string => trim($token));

        return match (true) {
            $tokens->contains('noindex') && $tokens->contains('nofollow') => self::NoindexNofollow,
            $tokens->contains('noindex') => self::NoindexFollow,
            $tokens->contains('index') || $tokens->contains('follow') => self::IndexFollow,
            default => null,
        };
    }
}
