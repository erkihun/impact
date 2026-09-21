<?php

declare(strict_types=1);

use App\Enums\ContentWorkflowState;
use App\Jobs\Search\SyncContentSearchDocumentJob;
use App\Models\ContentItem;
use App\Models\ContentVersion;
use App\Models\SearchDocument;
use App\Models\SearchQueryLog;
use App\Models\User;

it('indexes only a currently published content version and removes it immediately when archived', function (): void {
    $user = User::factory()->create();
    $content = ContentItem::query()->create([
        'type' => 'page',
        'owner_id' => $user->id,
        'status' => 'published',
        'published_at' => now('UTC'),
    ]);
    $version = ContentVersion::query()->create([
        'content_item_id' => $content->id,
        'locale' => 'en',
        'version_no' => 1,
        'slug' => 'quality-commitment',
        'title' => 'Quality commitment',
        'summary' => 'How delivery quality is governed.',
        'body' => ['text' => 'Evidence, review and learning.'],
        'workflow_state' => 'published',
        'created_by' => $user->id,
        'content_hash' => hash('sha256', 'quality'),
    ]);
    $content->update(['current_version_id' => $version->id]);

    SyncContentSearchDocumentJob::dispatchSync($content->id);
    $this->assertDatabaseHas('search_documents', [
        'searchable_type' => 'content_page',
        'searchable_id' => $content->id,
        'locale' => 'en',
    ]);

    $content->update(['status' => ContentWorkflowState::Archived]);
    $version->update(['workflow_state' => ContentWorkflowState::Archived]);
    SyncContentSearchDocumentJob::dispatchSync($content->id);
    expect(SearchDocument::query()->count())->toBe(0);
});

it('logs only a keyed query hash and ranks an exact title first', function (): void {
    foreach (['Impact strategy', 'Impact strategy for institutions'] as $index => $title) {
        SearchDocument::query()->create([
            'searchable_type' => 'insight',
            'searchable_id' => (string) str()->uuid(),
            'locale' => 'en',
            'title' => $title,
            'summary' => 'Evidence-led guidance.',
            'body' => 'Evidence-led guidance.',
            'url' => '/en/insights/result-'.$index,
            'published_at' => now('UTC')->subMinutes($index),
        ]);
    }

    $this->get('/en/search?q=Impact%20strategy')
        ->assertOk()
        ->assertSeeInOrder(['Impact strategy', 'Impact strategy for institutions']);

    $log = SearchQueryLog::query()->sole();
    expect($log->query_hash)->toHaveLength(64)
        ->and($log->query_hash)->not->toBe('Impact strategy')
        ->and($log->result_count)->toBe(2);
});
