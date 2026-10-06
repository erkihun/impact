<?php

declare(strict_types=1);

namespace App\Data\Seo;

use App\Enums\Seo\SeoIssueSeverity;

/**
 * Outcome of an SEO check, reported as "SEO ready", "SEO warnings" or
 * "SEO blocking issues". Deliberately not a numeric score.
 */
final class SeoValidationResult
{
    /** @var list<SeoIssue> */
    private array $issues = [];

    /** @param  list<SeoIssue>  $issues */
    public function __construct(array $issues = [])
    {
        foreach ($issues as $issue) {
            $this->add($issue);
        }
    }

    public function add(SeoIssue $issue): self
    {
        $this->issues[] = $issue;

        return $this;
    }

    public function merge(self $other): self
    {
        foreach ($other->issues() as $issue) {
            $this->add($issue);
        }

        return $this;
    }

    /** @return list<SeoIssue> */
    public function issues(?SeoIssueSeverity $severity = null): array
    {
        $issues = $severity === null
            ? $this->issues
            : array_values(array_filter($this->issues, static fn (SeoIssue $issue): bool => $issue->severity === $severity));
        usort($issues, static fn (SeoIssue $a, SeoIssue $b): int => [$a->severity->rank(), $a->code, (string) $a->url] <=> [$b->severity->rank(), $b->code, (string) $b->url]);

        return $issues;
    }

    public function count(SeoIssueSeverity $severity): int
    {
        return count($this->issues($severity));
    }

    public function countCode(string $code): int
    {
        return count(array_filter($this->issues, static fn (SeoIssue $issue): bool => $issue->code === $code));
    }

    public function hasBlocking(): bool
    {
        return $this->count(SeoIssueSeverity::Blocking) > 0;
    }

    public function status(): string
    {
        return match (true) {
            $this->hasBlocking() => 'blocking',
            $this->count(SeoIssueSeverity::Warning) > 0 => 'warnings',
            default => 'ready',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'blocking' => 'SEO blocking issues',
            'warnings' => 'SEO warnings',
            default => 'SEO ready',
        };
    }

    /** @return array{status: string, label: string, blocking: int, warnings: int, information: int, issues: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return [
            'status' => $this->status(),
            'label' => $this->statusLabel(),
            'blocking' => $this->count(SeoIssueSeverity::Blocking),
            'warnings' => $this->count(SeoIssueSeverity::Warning),
            'information' => $this->count(SeoIssueSeverity::Information),
            'issues' => array_map(static fn (SeoIssue $issue): array => $issue->toArray(), $this->issues()),
        ];
    }
}
