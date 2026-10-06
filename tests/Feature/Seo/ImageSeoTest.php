<?php

declare(strict_types=1);

use App\Models\MediaAsset;
use App\Models\MediaVariant;
use App\Services\Seo\ResponsiveImages;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function seoMediaAsset(string $visibility, ?string $alt = 'Portrait of Jane Doe'): MediaAsset
{
    $asset = MediaAsset::query()->create([
        'original_name' => 'portrait.png', 'disk' => 'public', 'path' => 'media/2026/10/'.Str::random(8).'/portrait.png',
        'mime_type' => 'image/png', 'size_bytes' => 4_800_000, 'sha256' => str_repeat('a', 64),
        'visibility' => $visibility, 'scan_status' => 'clean', 'processing_status' => 'ready', 'alt_text' => $alt,
    ]);
    foreach (['sm' => [480, 600], 'md' => [960, 1200], 'lg' => [1600, 2000]] as $name => [$width, $height]) {
        MediaVariant::query()->create([
            'media_asset_id' => $asset->id, 'variant' => $name, 'path' => "media/2026/10/{$asset->id}/variants/{$name}.webp",
            'width' => $width, 'height' => $height, 'size_bytes' => 40_000,
        ]);
    }

    return $asset;
}

it('serves the hero as responsive AVIF/WebP derivatives with dimensions, eager priority and a preload', function (): void {
    $response = $this->get('/')->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->where('heroSlider.slides.0.picture.src', '/images/optimized/ethiopia-highlands-1440.webp')
        ->where('heroSlider.slides.0.picture.width', 1440)
        ->where('heroSlider.slides.0.picture.height', 928)
        ->where('heroSlider.slides.0.picture.sizes', '100vw')
        ->where('heroSlider.slides.0.picture.avifSrcset', fn (string $srcset) => str_contains($srcset, 'ethiopia-highlands-480.avif 480w') && str_contains($srcset, '1920w')));
    $response->assertSee('rel="preload" as="image" type="image/avif"', false)
        ->assertSee('fetchpriority="high"', false)
        ->assertDontSee('ethiopia-highlands.jpg', false);
});

it('keeps the high-resolution source out of the public directory and inside every derivative budget', function (): void {
    expect(file_exists(public_path('images/ethiopia-highlands.jpg')))->toBeFalse()
        ->and(file_exists(resource_path('images/source/ethiopia-highlands.jpg')))->toBeTrue();

    $manifest = app(ResponsiveImages::class)->manifest()['ethiopia-highlands'];
    foreach ($manifest['variants'] as $variant) {
        expect($variant['bytes'])->toBeLessThan(1024 * 1024, $variant['path']);
        if ($variant['width'] <= 1440) {
            expect($variant['bytes'])->toBeLessThan(500 * 1024, $variant['path']);
        }
    }
    expect($manifest['social'])->toMatchArray(['width' => 1200, 'height' => 630, 'format' => 'jpeg']);
});

it('delivers public media as WebP derivatives with srcset, dimensions and alternative text, never the large original', function (): void {
    $photo = seoMediaAsset('public');
    publishExpert('jane-doe', 'Jane Doe', $photo);

    $this->get('/experts/jane-doe')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('sections.0.photo', "/storage/media/2026/10/{$photo->id}/variants/md.webp")
        ->where('sections.0.photoWidth', 960)
        ->where('sections.0.photoHeight', 1200)
        ->where('sections.0.photoAlt', 'Portrait of Jane Doe')
        ->where('sections.0.photoSrcset', fn (string $srcset) => substr_count($srcset, 'w,') === 2));

    $this->get('/experts')->assertInertia(fn (Assert $page) => $page
        ->where('items.0.photo', "/storage/media/2026/10/{$photo->id}/variants/sm.webp")
        ->where('items.0.photoWidth', 480));
});

it('falls back to a meaningful alt text and never exposes private media', function (): void {
    $private = seoMediaAsset('restricted');
    publishExpert('private-photo', 'Private Photo', $private);
    $unlabelled = seoMediaAsset('public', null);
    publishExpert('no-alt', 'No Alt', $unlabelled);

    $this->get('/experts/private-photo')->assertInertia(fn (Assert $page) => $page->where('sections.0.photo', null));
    expect($this->get('/experts/private-photo')->getContent())->not->toContain($private->path);
    $this->get('/experts/no-alt')->assertInertia(fn (Assert $page) => $page->where('sections.0.photoAlt', 'No Alt'));
});

it('uses an approved public image for the social card and the configured fallback otherwise', function (): void {
    asProduction();
    $photo = seoMediaAsset('public');
    publishExpert('jane-doe', 'Jane Doe', $photo);

    $this->get('https://localhost/experts/jane-doe')
        ->assertSee('property="og:image" content="https://localhost/storage/'.$photo->path.'"', false)
        ->assertSee('property="og:image:alt" content="Portrait of Jane Doe"', false);
});
