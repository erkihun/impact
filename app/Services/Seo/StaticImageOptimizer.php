<?php

declare(strict_types=1);

namespace App\Services\Seo;

use GdImage;
use RuntimeException;

/**
 * Generates public derivatives for high-resolution design assets.
 *
 * Sources live in resources/images/source and are never served. Each source
 * gets WebP (and AVIF where GD supports it) widths for srcset plus a
 * 1200×630 JPEG social card. Files are written atomically and described in
 * public/images/optimized/manifest.json.
 */
final class StaticImageOptimizer
{
    /** @var list<int> */
    public const WIDTHS = [480, 960, 1440, 1920];

    public const SOURCE_DIRECTORY = 'images/source';

    public const OUTPUT_DIRECTORY = 'images/optimized';

    /** @return array<string, array<string, mixed>> */
    public function optimizeAll(): array
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('GD with WebP support is required to optimize images.');
        }

        $manifest = [];
        foreach (glob(resource_path(self::SOURCE_DIRECTORY.'/*.{jpg,jpeg,png}'), GLOB_BRACE) ?: [] as $source) {
            $name = pathinfo($source, PATHINFO_FILENAME);
            $manifest[$name] = $this->optimize($source, $name);
        }
        ksort($manifest);

        $this->atomicWrite(
            public_path(self::OUTPUT_DIRECTORY.'/manifest.json'),
            (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );

        return $manifest;
    }

    /** @return array<string, mixed> */
    private function optimize(string $source, string $name): array
    {
        $image = @imagecreatefromstring((string) file_get_contents($source));
        if (! $image instanceof GdImage) {
            throw new RuntimeException("Unreadable image: {$source}");
        }
        $width = imagesx($image);
        $height = imagesy($image);
        $variants = [];

        foreach (self::WIDTHS as $target) {
            if ($target > $width && $target !== self::WIDTHS[0]) {
                continue;
            }
            $resizedWidth = min($target, $width);
            $resizedHeight = (int) round($height * ($resizedWidth / $width));
            $resized = $this->resize($image, $resizedWidth, $resizedHeight);

            $variants[] = $this->write($resized, "{$name}-{$resizedWidth}.webp", 'webp', $resizedWidth, $resizedHeight);
            if (function_exists('imageavif')) {
                $variants[] = $this->write($resized, "{$name}-{$resizedWidth}.avif", 'avif', $resizedWidth, $resizedHeight);
            }
            imagedestroy($resized);
        }

        $social = $this->socialCard($image, $width, $height);
        $socialEntry = $this->write($social, "{$name}-social.jpg", 'jpeg', 1200, 630);
        imagedestroy($social);
        imagedestroy($image);

        return [
            'source' => 'resources/'.self::SOURCE_DIRECTORY.'/'.basename($source),
            'source_bytes' => filesize($source),
            'width' => $width,
            'height' => $height,
            'variants' => $variants,
            'social' => $socialEntry,
        ];
    }

    private function resize(GdImage $image, int $width, int $height): GdImage
    {
        $resized = imagecreatetruecolor($width, $height);
        if (! $resized instanceof GdImage) {
            throw new RuntimeException('Unable to allocate an image derivative.');
        }
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $resized;
    }

    /** Centre crop to the 1.91:1 ratio social platforms expect. */
    private function socialCard(GdImage $image, int $width, int $height): GdImage
    {
        $ratio = 1200 / 630;
        $cropWidth = $width;
        $cropHeight = (int) round($width / $ratio);
        if ($cropHeight > $height) {
            $cropHeight = $height;
            $cropWidth = (int) round($height * $ratio);
        }
        $card = imagecreatetruecolor(1200, 630);
        if (! $card instanceof GdImage) {
            throw new RuntimeException('Unable to allocate the social card.');
        }
        imagecopyresampled($card, $image, 0, 0, (int) (($width - $cropWidth) / 2), (int) (($height - $cropHeight) / 2), 1200, 630, $cropWidth, $cropHeight);

        return $card;
    }

    /** @return array{path: string, format: string, width: int, height: int, bytes: int} */
    private function write(GdImage $image, string $file, string $format, int $width, int $height): array
    {
        ob_start();
        $ok = match ($format) {
            'webp' => imagewebp($image, null, $width > 1440 ? 64 : 76),
            'avif' => imageavif($image, null, $width > 1440 ? 44 : 50, 6),
            default => imagejpeg($image, null, 82),
        };
        $bytes = (string) ob_get_clean();
        if (! $ok || $bytes === '') {
            throw new RuntimeException("Unable to encode {$file}.");
        }

        $relative = self::OUTPUT_DIRECTORY.'/'.$file;
        $this->atomicWrite(public_path($relative), $bytes);

        return ['path' => $relative, 'format' => $format, 'width' => $width, 'height' => $height, 'bytes' => strlen($bytes)];
    }

    private function atomicWrite(string $path, string $contents): void
    {
        if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true) && ! is_dir(dirname($path))) {
            throw new RuntimeException('Unable to create '.dirname($path));
        }
        $temporary = $path.'.'.bin2hex(random_bytes(6)).'.tmp';
        if (file_put_contents($temporary, $contents) === false || ! rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException("Unable to write {$path}");
        }
    }
}
