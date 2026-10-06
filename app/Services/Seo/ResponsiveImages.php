<?php

declare(strict_types=1);

namespace App\Services\Seo;

use Illuminate\Support\Facades\Cache;

/**
 * Reads the derivative manifest written by `php artisan seo:images-optimize`
 * and returns ready-to-render responsive image attributes. High-resolution
 * sources stay in resources/images/source and are never served directly.
 */
final class ResponsiveImages
{
    public const MANIFEST = 'images/optimized/manifest.json';

    /** @return array<string, array<string, mixed>> */
    public function manifest(): array
    {
        $path = public_path(self::MANIFEST);
        $version = is_file($path) ? (string) filemtime($path) : 'missing';

        return Cache::driver('array')->rememberForever('seo.images.manifest.'.$version, static function () use ($path): array {
            if (! is_file($path)) {
                return [];
            }
            $decoded = json_decode((string) file_get_contents($path), true);

            return is_array($decoded) ? $decoded : [];
        });
    }

    /**
     * @return array{src: string, srcset: string, avifSrcset: string|null, width: int, height: int, sizes: string}|null
     */
    public function attributes(string $name, string $sizes = '100vw', int $fallbackWidth = 1600): ?array
    {
        $entry = $this->manifest()[$name] ?? null;
        if (! is_array($entry) || empty($entry['variants'])) {
            return null;
        }

        $variants = collect($entry['variants']);
        $webp = $variants->where('format', 'webp')->sortBy('width')->values();
        $avif = $variants->where('format', 'avif')->sortBy('width')->values();
        if ($webp->isEmpty()) {
            return null;
        }
        $fallback = $webp->first(fn (array $variant): bool => $variant['width'] >= $fallbackWidth) ?? $webp->last();
        $srcset = static fn ($set): string => $set
            ->map(fn (array $variant): string => '/'.$variant['path'].' '.$variant['width'].'w')
            ->implode(', ');

        return [
            'src' => '/'.$fallback['path'],
            'srcset' => $srcset($webp),
            'avifSrcset' => $avif->isEmpty() ? null : $srcset($avif),
            'width' => (int) $fallback['width'],
            'height' => (int) $fallback['height'],
            'sizes' => $sizes,
        ];
    }

    /** @return array{path: string, width: int, height: int}|null */
    public function socialCard(string $name): ?array
    {
        $social = $this->manifest()[$name]['social'] ?? null;

        return is_array($social) ? ['path' => '/'.$social['path'], 'width' => (int) $social['width'], 'height' => (int) $social['height']] : null;
    }
}
