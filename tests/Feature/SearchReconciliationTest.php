<?php

declare(strict_types=1);

use App\Models\Expert;
use App\Models\ExpertVersion;
use App\Models\SearchDocument;
use App\Models\Service;
use App\Models\ServiceVersion;
use App\Models\User;

it('indexes only the latest publicly visible version and deletes stale search documents', function (): void {
    $author = User::factory()->create();
    $service = Service::query()->create([
        'code' => 'RECONCILE',
        'status' => 'published',
        'created_by' => $author->id,
    ]);
    $old = searchServiceVersion($service, 1, 'old-service-version');
    $current = searchServiceVersion($service, 2, 'current-service-version');
    SearchDocument::query()->create([
        'searchable_type' => 'service',
        'searchable_id' => $old->id,
        'locale' => 'en',
        'title' => 'Stale version',
        'body' => 'Stale',
        'url' => '/en/services/old-service-version',
        'published_at' => now(),
    ]);
    SearchDocument::query()->create([
        'searchable_type' => 'vacancy',
        'searchable_id' => (string) str()->uuid7(),
        'locale' => 'en',
        'title' => 'Deleted vacancy',
        'body' => 'Stale',
        'url' => '/en/careers/deleted',
        'published_at' => now(),
    ]);

    $this->artisan('impact:search:reconcile')->assertSuccessful();

    $this->assertDatabaseHas('search_documents', [
        'searchable_type' => 'service',
        'searchable_id' => $current->id,
        'locale' => 'en',
        'title' => 'Current Service Version',
    ]);
    $this->assertDatabaseMissing('search_documents', ['searchable_id' => $old->id]);
    $this->assertDatabaseMissing('search_documents', ['title' => 'Deleted vacancy']);
    expect(SearchDocument::query()->where('searchable_type', 'service')->count())->toBe(1);
});

it('prevents parent-state and consent bypass on public detail routes and reconciliation', function (): void {
    $author = User::factory()->create();
    $service = Service::query()->create([
        'code' => 'HIDDEN',
        'status' => 'draft',
        'created_by' => $author->id,
    ]);
    $serviceVersion = searchServiceVersion($service, 1, 'hidden-parent-service');
    $expert = Expert::query()->create([
        'status' => 'published',
        'public_email_enabled' => false,
        'publication_authorized_at' => null,
    ]);
    $expertVersion = ExpertVersion::query()->create([
        'expert_id' => $expert->id,
        'locale' => 'en',
        'version_no' => 1,
        'slug' => 'unauthorized-expert',
        'display_name' => 'Unauthorized Expert',
        'professional_title' => 'Should not publish',
        'biography' => 'Publication authority is absent.',
        'workflow_state' => 'published',
    ]);

    $this->get('/en/services/'.$serviceVersion->slug)->assertNotFound();
    $this->get('/en/experts/'.$expertVersion->slug)->assertNotFound();
    $this->artisan('impact:search:reconcile')->assertSuccessful();

    $this->assertDatabaseMissing('search_documents', ['searchable_id' => $serviceVersion->id]);
    $this->assertDatabaseMissing('search_documents', ['searchable_id' => $expertVersion->id]);
});

function searchServiceVersion(Service $service, int $version, string $slug): ServiceVersion
{
    return ServiceVersion::query()->create([
        'service_id' => $service->id,
        'locale' => 'en',
        'version_no' => $version,
        'slug' => $slug,
        'name' => str($slug)->replace('-', ' ')->title()->toString(),
        'summary' => 'Public search summary.',
        'approach' => 'Evidence-led delivery.',
        'deliverables' => ['Assessment'],
        'workflow_state' => 'published',
    ]);
}
