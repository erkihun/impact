<?php

declare(strict_types=1);

namespace App\Support\Settings;

final readonly class MediaSettings
{
    public function __construct(private EffectiveSettings $settings) {}

    /** @return list<string> */
    public function allowedExtensions(): array
    {
        $operational = collect(explode(',', $this->settings->string('media.allowed_extensions')))
            ->map(static fn (string $extension): string => strtolower(trim($extension)))
            ->filter()
            ->values();
        $server = collect(config('impact.files.engagement_allowed_extensions', []))
            ->map(static fn (string $extension): string => strtolower($extension));

        return $operational->intersect($server)->values()->all();
    }

    public function maximumImageKilobytes(): int
    {
        return min(
            $this->settings->integer('media.max_image_size_kb'),
            (int) config('impact.files.media_image_max_kilobytes', 5120),
        );
    }

    public function requiresAltText(): bool
    {
        return $this->settings->boolean('media.require_alt_text');
    }

    public function requiresMalwareScan(): bool
    {
        return $this->settings->boolean('media.malware_scanning_required');
    }
}
