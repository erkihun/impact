<?php

declare(strict_types=1);

namespace App\Actions\PageComposition;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Enums\PageCompositionState;
use App\Enums\PageSectionType;
use App\Events\PageCompositionPublished;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\PageComposition;
use App\Models\User;
use App\Support\PageSectionRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class TransitionPageCompositionAction
{
    /** @var array<string, list<PageCompositionState>> */
    private const TRANSITIONS = [
        'draft' => [PageCompositionState::InReview],
        'changes_requested' => [PageCompositionState::InReview],
        'in_review' => [PageCompositionState::ChangesRequested, PageCompositionState::Approved],
        'approved' => [PageCompositionState::Scheduled, PageCompositionState::Published],
        'scheduled' => [PageCompositionState::Published, PageCompositionState::Archived],
        'published' => [PageCompositionState::Archived],
    ];

    public function __construct(
        private AuditRecorder $audit,
        private PageSectionRegistry $registry,
    ) {}

    public function execute(
        User $actor,
        PageComposition $composition,
        PageCompositionState $destination,
        string $correlationId,
        ?string $comment = null,
    ): PageComposition {
        return DB::transaction(function () use (
            $actor, $composition, $destination, $correlationId, $comment,
        ): PageComposition {
            $requiredPermission = match ($destination) {
                PageCompositionState::Approved,
                PageCompositionState::ChangesRequested => 'pages.approve',
                PageCompositionState::Scheduled,
                PageCompositionState::Published,
                PageCompositionState::Archived => 'pages.publish',
                default => 'pages.update',
            };
            abort_unless($actor->hasPermission($requiredPermission), 403);

            $locked = PageComposition::query()->lockForUpdate()->findOrFail($composition->getKey());
            $source = $locked->state;
            if (! in_array($destination, self::TRANSITIONS[$source->value] ?? [], true)) {
                throw new InvalidStateTransitionException($source, $destination);
            }
            if (in_array($destination, [
                PageCompositionState::InReview,
                PageCompositionState::Approved,
                PageCompositionState::Scheduled,
                PageCompositionState::Published,
            ], true)) {
                $this->ensurePublishable($locked);
            }

            if ($destination === PageCompositionState::Published) {
                PageComposition::query()
                    ->where('page_key', $locked->page_key)
                    ->where('locale', $locked->locale)
                    ->where($locked->getKeyName(), '!=', $locked->getKey())
                    ->where('state', PageCompositionState::Published)
                    ->update([
                        'state' => PageCompositionState::Archived->value,
                        'updated_at' => now('UTC'),
                    ]);
            }

            $locked->forceFill([
                'state' => $destination,
                'published_at' => $destination === PageCompositionState::Published
                    ? now('UTC')
                    : $locked->published_at,
                'content_hash' => hash(
                    'sha256',
                    $locked->content_hash.'|'.$destination->value.'|'.now('UTC')->toAtomString(),
                ),
            ])->save();

            DB::table('page_composition_workflow_events')->insert([
                'id' => (string) Str::uuid7(),
                'page_composition_id' => $locked->getKey(),
                'from_state' => $source->value,
                'to_state' => $destination->value,
                'actor_id' => $actor->getKey(),
                'comment' => $comment,
                'correlation_id' => $correlationId,
                'created_at' => now('UTC'),
            ]);
            $this->audit->record(new AuditData(
                action: 'page_composition.transitioned',
                auditableType: PageComposition::class,
                auditableId: (string) $locked->getKey(),
                actorId: (string) $actor->getKey(),
                correlationId: $correlationId,
                afterHash: $locked->content_hash,
                metadata: ['from' => $source->value, 'to' => $destination->value, 'comment' => $comment],
            ));

            Cache::forget("public-page:{$locked->page_key}:{$locked->locale}");
            if ($destination === PageCompositionState::Published) {
                PageCompositionPublished::dispatch($locked->refresh());
            }

            return $locked->refresh();
        }, attempts: 3);
    }

    private function ensurePublishable(PageComposition $composition): void
    {
        $composition->load('sections.currentVersion.media.asset');
        $headingCount = $composition->sections->filter(
            fn ($section): bool => $section->currentVersion?->enabled
                && in_array($section->currentVersion->type, [
                    PageSectionType::HomepageHero,
                    PageSectionType::PageHeader,
                    PageSectionType::FormIntroduction,
                ], true),
        )->count();
        if ($composition->sections->isEmpty() || $headingCount !== 1) {
            throw ValidationException::withMessages([
                'composition' => __('A publishable page requires sections and exactly one enabled H1 section.'),
            ]);
        }
        foreach ($composition->sections as $section) {
            $version = $section->currentVersion;
            if ($version === null) {
                throw ValidationException::withMessages([
                    'composition' => __('Every section requires a current immutable version.'),
                ]);
            }
            $this->registry->validate(
                $version->type,
                $version->variant,
                $version->content,
                $version->presentation,
            );
            if ($version->media->contains(
                fn ($usage): bool => ! $usage->asset->isPubliclyUsable(),
            )) {
                throw ValidationException::withMessages([
                    'media' => __('All page media must be public, clean and processed before review.'),
                ]);
            }
        }
    }
}
