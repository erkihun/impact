<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Data\Seo\SeoImage;
use App\Models\MediaAsset;
use App\Models\MediaVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Public image markup data for approved media.
 *
 * Pages receive optimized WebP derivatives with intrinsic dimensions and a
 * srcset instead of the uploaded original, so a large source PNG is never
 * the delivered image. Private or unapproved media produce nothing.
 */
final readonly class MediaImagePresenter
{
    public function __construct(private CanonicalUrlBuilder $urls) {}

    /**
     * @return array{src: string, srcset: string|null, sizes: string, width: int|null, height: int|null, alt: string, decorative: bool}|null
     */
    public function attributes(?MediaAsset $asset, string $sizes = '100vw', string $preferred = 'md', ?string $fallbackAlt = null): ?array
    {
        if (! $asset instanceof MediaAsset || ! $asset->isPubliclyUsable()) {
            return null;
        }

        $variants = $this->variants($asset);
        $chosen = $variants->get($preferred) ?? $variants->sortByDesc('width')->first();
        $srcset = $variants->isEmpty() ? null : $variants
            ->sortBy('width')
            ->map(fn (MediaVariant $variant): string => $this->variantUrl($asset, $variant).' '.$variant->width.'w')
            ->implode(', ');
        $alt = trim((string) $asset->alt_text);

        return [
            'src' => $chosen instanceof MediaVariant ? $this->variantUrl($asset, $chosen) : $asset->publicUrl(),
            'srcset' => $srcset,
            'sizes' => $sizes,
            'width' => $chosen instanceof MediaVariant ? (int) $chosen->width : null,
            'height' => $chosen instanceof MediaVariant ? (int) $chosen->height : null,
            'alt' => $alt !== '' ? $alt : (string) $fallbackAlt,
            'decorative' => $alt === '' && $fallbackAlt === null,
        ];
    }

    /**
     * Image for Open Graph / structured data. JPEG and PNG originals are
     * widely supported by social platforms; anything else uses the largest
     * WebP derivative.
     */
    public function seoImage(?MediaAsset $asset, string $fallbackAlt): ?SeoImage
    {
        if (! $asset instanceof MediaAsset || ! $asset->isPubliclyUsable()) {
            return null;
        }

        $largest = $this->variants($asset)->sortByDesc('width')->first();
        $alt = trim((string) $asset->alt_text) ?: $fallbackAlt;
        if (in_array($asset->mime_type, ['image/jpeg', 'image/png'], true) || ! $largest instanceof MediaVariant) {
            return new SeoImage($this->urls->absolute($asset->publicUrl()), $alt, null, null, (string) $asset->mime_type);
        }

        return new SeoImage(
            $this->urls->absolute($this->variantUrl($asset, $largest)),
            $alt,
            (int) $largest->width,
            (int) $largest->height,
            'image/webp',
        );
    }

    /** @return Collection<string, MediaVariant> */
    private function variants(MediaAsset $asset): Collection
    {
        return $asset->variants
            ->filter(static fn (MediaVariant $variant): bool => (int) $variant->width > 0 && (int) $variant->height > 0)
            ->keyBy('variant');
    }

    private function variantUrl(MediaAsset $asset, MediaVariant $variant): string
    {
        $disk = (string) $asset->disk;
        if ($disk === (string) config('impact.files.public_disk', 'public')
            && config("filesystems.disks.{$disk}.driver") === 'local') {
            return '/storage/'.ltrim((string) $variant->path, '/');
        }

        return Storage::disk($disk)->url((string) $variant->path);
    }
}
