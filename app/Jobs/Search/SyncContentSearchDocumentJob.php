<?php

declare(strict_types=1);

namespace App\Jobs\Search;

use App\Contracts\SearchIndexer;
use App\Enums\ContentWorkflowState;
use App\Models\ContentItem;
use App\Services\Seo\PublicUrlGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncContentSearchDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var list<int> */
    public array $backoff = [15, 60, 300];

    public function __construct(public readonly string $contentItemId)
    {
        $this->onQueue('search');
    }

    public function handle(SearchIndexer $indexer, PublicUrlGenerator $paths): void
    {
        $content = ContentItem::query()->with('currentVersion')->findOrFail($this->contentItemId);
        $version = $content->currentVersion;
        if ($version === null) {
            return;
        }
        $type = 'content_'.$content->getRawOriginal('type');
        // Only content with a real public page is searchable; a result must
        // never link to a URL that does not exist.
        $url = $paths->contentItemPath($content, $version);
        if ($url === null
            || $content->getRawOriginal('status') !== ContentWorkflowState::Published->value
            || $version->getRawOriginal('workflow_state') !== ContentWorkflowState::Published->value) {
            $indexer->delete($type, $content->id, $version->locale);

            return;
        }

        $body = collect($version->body)->flatten()
            ->filter(static fn (mixed $value): bool => is_scalar($value))
            ->join(' ');
        $indexer->upsert($type, $content->id, $version->locale, [
            'title' => $version->title,
            'summary' => $version->summary,
            'body' => $body,
            'url' => $url,
            'filters' => [$content->getRawOriginal('type')],
            'published_at' => $content->getRawOriginal('published_at'),
        ]);
    }
}
