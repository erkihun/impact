<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\MediaAsset;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Imagick;
use RuntimeException;

final class ImageVariantGenerator
{
    /** @var array<string, int> */
    private const VARIANT_WIDTHS = [
        'sm' => 480,
        'md' => 960,
        'lg' => 1600,
    ];

    /**
     * @return list<array{variant: string, path: string, width: int, height: int, size_bytes: int}>
     */
    public function generate(MediaAsset $asset): array
    {
        $bytes = Storage::disk($asset->disk)->get($asset->path);

        if (class_exists(Imagick::class)) {
            return $this->generateWithImagick($asset, $bytes);
        }

        if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
            return $this->generateWithGd($asset, $bytes);
        }

        throw new RuntimeException('Image processing requires the Imagick extension or GD with WebP support.');
    }

    /**
     * @return list<array{variant: string, path: string, width: int, height: int, size_bytes: int}>
     */
    private function generateWithImagick(MediaAsset $asset, string $bytes): array
    {
        $source = new Imagick;
        $source->readImageBlob($bytes);
        $source->setIteratorIndex(0);
        $source->autoOrient();
        $source->stripImage();

        if ($source->getImageWidth() < 1 || $source->getImageHeight() < 1) {
            $source->clear();
            throw new RuntimeException('The image has invalid dimensions.');
        }

        $variants = [];
        foreach (self::VARIANT_WIDTHS as $name => $width) {
            $variant = clone $source;
            if ($variant->getImageWidth() > $width) {
                $variant->thumbnailImage($width, 0);
            }
            $variant->setImageFormat('webp');
            $variant->setImageCompressionQuality(82);
            $variant->stripImage();
            $blob = $variant->getImageBlob();
            $variants[] = $this->storeVariant(
                asset: $asset,
                name: $name,
                blob: $blob,
                width: $variant->getImageWidth(),
                height: $variant->getImageHeight(),
            );
            $variant->clear();
        }
        $source->clear();

        return $variants;
    }

    /**
     * @return list<array{variant: string, path: string, width: int, height: int, size_bytes: int}>
     */
    private function generateWithGd(MediaAsset $asset, string $bytes): array
    {
        $source = @imagecreatefromstring($bytes);
        if (! $source instanceof GdImage || imagesx($source) < 1 || imagesy($source) < 1) {
            throw new RuntimeException('The image has invalid dimensions.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $variants = [];

        foreach (self::VARIANT_WIDTHS as $name => $maximumWidth) {
            $width = min($sourceWidth, $maximumWidth);
            $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));
            $variant = imagecreatetruecolor($width, $height);
            if (! $variant instanceof GdImage) {
                imagedestroy($source);
                throw new RuntimeException('An image variant could not be created.');
            }

            imagealphablending($variant, false);
            imagesavealpha($variant, true);
            $transparent = imagecolorallocatealpha($variant, 0, 0, 0, 127);
            imagefill($variant, 0, 0, $transparent);
            imagecopyresampled(
                $variant,
                $source,
                0,
                0,
                0,
                0,
                $width,
                $height,
                $sourceWidth,
                $sourceHeight,
            );

            ob_start();
            $encoded = imagewebp($variant, null, 82);
            $blob = ob_get_clean();
            imagedestroy($variant);

            if (! $encoded || ! is_string($blob)) {
                imagedestroy($source);
                throw new RuntimeException('An image variant could not be encoded.');
            }

            $variants[] = $this->storeVariant(
                asset: $asset,
                name: $name,
                blob: $blob,
                width: $width,
                height: $height,
            );
        }

        imagedestroy($source);

        return $variants;
    }

    /**
     * @return array{variant: string, path: string, width: int, height: int, size_bytes: int}
     */
    private function storeVariant(
        MediaAsset $asset,
        string $name,
        string $blob,
        int $width,
        int $height,
    ): array {
        $path = 'quarantine/processed/'.$asset->id."/{$name}.webp";
        if (! Storage::disk($asset->disk)->put($path, $blob, ['visibility' => 'private'])) {
            throw new RuntimeException('An image variant could not be stored.');
        }

        return [
            'variant' => $name,
            'path' => $path,
            'width' => $width,
            'height' => $height,
            'size_bytes' => strlen($blob),
        ];
    }
}
