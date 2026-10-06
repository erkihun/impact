<?php

declare(strict_types=1);

use App\Enums\Seo\SeoIssueSeverity;
use App\Models\Redirect;
use App\Models\SeoAuditRun;
use App\Services\Seo\SeoAuditService;

beforeEach(function (): void {
    asProduction();
    config(['app.url' => 'https://localhost']);
});

it('passes the strict audit for a clean English-only site', function (): void {
    $service = publishService('digital-transformation');
    $industry = publishIndustry('public-sector');
    DB::table('service_industry')->insert(['service_id' => $service->service_id, 'industry_id' => $industry->industry_id, 'sort_order' => 0, 'featured' => false]);

    $report = app(SeoAuditService::class)->run();

    expect(collect($report['result']->issues(SeoIssueSeverity::Blocking))->map->toArray()->all())->toBe([])
        ->and($report['metrics']['indexable_pages'])->toBeGreaterThan(15);
    $this->artisan('seo:audit', ['--strict' => true])->assertExitCode(0);
    $this->artisan('seo:audit', ['--format' => 'json'])->assertExitCode(0);
});

it('fails the strict audit on blocking defects', function (): void {
    publishService('digital-transformation');
    Redirect::query()->create(['source_path' => '/loop-a', 'destination_url' => '/loop-b', 'status_code' => 301, 'enabled' => true]);
    Redirect::query()->create(['source_path' => '/loop-b', 'destination_url' => '/loop-a', 'status_code' => 301, 'enabled' => true]);

    $this->artisan('seo:audit', ['--strict' => true])->assertExitCode(1);
});

it('flags duplicate titles, fallback descriptions and orphan pages without inventing scores', function (): void {
    publishService('one', 'Strategy', ['summary' => null, 'problem_statement' => null, 'approach' => null]);
    publishIndustry('strategy', 'Strategy');

    $report = app(SeoAuditService::class)->run(persist: true);
    $codes = collect($report['result']->issues())->pluck('code');

    expect($codes)->toContain('description-missing', 'orphan-page')
        ->and($report['result']->status())->toBe('warnings')
        ->and($report['metrics'])->not->toHaveKey('score');
    expect(SeoAuditRun::query()->sole()->metrics['pages'])->not->toBeEmpty();
});

it('validates the sitemap, structured data and links from the command line', function (): void {
    publishService('digital-transformation');
    publishExpert('jane-doe');

    $this->artisan('seo:sitemap-generate')->assertExitCode(0);
    $this->artisan('seo:sitemap-validate')->assertExitCode(0);
    $this->artisan('seo:structured-data-validate')->assertExitCode(0);
    $this->artisan('seo:links-check')->assertExitCode(0);
    $this->artisan('seo:redirects-validate')->assertExitCode(0);
});
