<?php

declare(strict_types=1);

use App\Actions\Seo\SaveRedirectAction;
use App\Enums\Seo\RedirectOrigin;
use App\Models\Redirect;
use App\Services\Seo\RedirectAuditor;
use Illuminate\Validation\ValidationException;

it('creates a permanent redirect when a published slug changes, recording origin and subject', function (): void {
    $service = publishService('digital-transformation');

    $service->update(['slug' => 'digital-government']);

    $redirect = Redirect::query()->where('source_path', '/services/digital-transformation')->sole();
    expect($redirect->destination_url)->toBe('/services/digital-government')
        ->and($redirect->status_code)->toBe(301)
        ->and($redirect->origin)->toBe(RedirectOrigin::SlugChange)
        ->and($redirect->subject_key)->toBe($service->service_id);
    $this->get('/services/digital-transformation')->assertStatus(301)->assertRedirect('/services/digital-government');
});

it('keeps slug history single-hop after repeated slug changes', function (): void {
    $service = publishService('first-slug');
    $service->update(['slug' => 'second-slug']);
    $service->update(['slug' => 'third-slug']);

    expect(Redirect::query()->where('source_path', '/services/first-slug')->value('destination_url'))->toBe('/services/third-slug')
        ->and(Redirect::query()->where('source_path', '/services/second-slug')->value('destination_url'))->toBe('/services/third-slug');
    $this->get('/services/first-slug')->assertRedirect('/services/third-slug');
});

it('records 410 Gone when the last public version of a resource is deleted', function (): void {
    $expert = publishExpert('jane-doe');
    $expert->delete();

    expect(Redirect::query()->where('source_path', '/experts/jane-doe')->value('status_code'))->toBe(410);
    $this->get('/experts/jane-doe')->assertStatus(410);
});

it('rejects loops, self-redirects, external targets and redirects that hide a live page', function (string $source, string $destination, string $field): void {
    publishService('live-service');
    Redirect::query()->create(['source_path' => '/b', 'destination_url' => '/a', 'status_code' => 301, 'enabled' => true]);

    expect(fn () => app(SaveRedirectAction::class)->execute($source, $destination))
        ->toThrow(fn (ValidationException $exception) => expect($exception->errors())->toHaveKey($field));
})->with([
    'loop' => ['/a', '/b', 'destination_url'],
    'self' => ['/c', '/c', 'destination_url'],
    'external' => ['/d', 'https://evil.example.com/x', 'destination_url'],
    'protocol relative' => ['/e', '//evil.example.com', 'destination_url'],
    'live page' => ['/services/live-service', '/services', 'source_path'],
    'admin' => ['/admin/users', '/services', 'source_path'],
]);

it('flattens chains when a redirect is added after the first hop', function (): void {
    publishService('final-service');
    app(SaveRedirectAction::class)->execute('/a', '/b');
    app(SaveRedirectAction::class)->execute('/b', '/services/final-service');

    expect(Redirect::query()->where('source_path', '/a')->value('destination_url'))->toBe('/services/final-service')
        ->and(Redirect::query()->where('source_path', '/b')->value('destination_url'))->toBe('/services/final-service');
});

it('stores a new redirect to a chained destination as its final hop', function (): void {
    publishService('final-service');
    app(SaveRedirectAction::class)->execute('/b', '/services/final-service');
    app(SaveRedirectAction::class)->execute('/a', '/b');

    expect(Redirect::query()->where('source_path', '/a')->value('destination_url'))->toBe('/services/final-service');
});

it('detects and flattens chains and loops written outside the action', function (): void {
    publishService('final-service');
    Redirect::query()->create(['source_path' => '/x', 'destination_url' => '/y', 'status_code' => 301, 'enabled' => true]);
    Redirect::query()->create(['source_path' => '/y', 'destination_url' => '/services/final-service', 'status_code' => 301, 'enabled' => true]);
    Redirect::query()->create(['source_path' => '/loop-a', 'destination_url' => '/loop-b', 'status_code' => 301, 'enabled' => true]);
    Redirect::query()->create(['source_path' => '/loop-b', 'destination_url' => '/loop-a', 'status_code' => 301, 'enabled' => true]);

    $codes = collect(app(RedirectAuditor::class)->validate()->issues())->pluck('code');
    expect($codes)->toContain('redirect-chain', 'redirect-loop');
    $this->artisan('seo:redirects-validate')->assertExitCode(1);

    expect(app(RedirectAuditor::class)->flatten())->toBe(1);
    expect(Redirect::query()->where('source_path', '/x')->value('destination_url'))->toBe('/services/final-service');
    // A loop is never followed by visitors.
    $this->get('/loop-a')->assertNotFound();
});

it('serves 410 for gone records and counts hits for redirects', function (): void {
    publishService('final-service');
    app(SaveRedirectAction::class)->execute('/old-page', '/services/final-service', reason: 'Merged');
    app(SaveRedirectAction::class)->execute('/withdrawn-report', null, 410, reason: 'Withdrawn');

    $this->get('/old-page?utm_source=x')->assertStatus(301)->assertRedirect('/services/final-service?utm_source=x');
    $this->get('/withdrawn-report')->assertStatus(410);
    expect(Redirect::query()->where('source_path', '/old-page')->value('hit_count'))->toBe(1);
});
