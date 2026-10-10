<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\ContentItem;
use App\Models\EngagementSubmission;
use App\Models\PageComposition;
use Illuminate\Support\Str;

/**
 * Turns an audit event into a sentence a non-technical reader understands
 * ("Selam Tadesse updated the service “Strategy”"), keeping the raw action,
 * reference and metadata available as technical details.
 */
final class AuditEventPresenter
{
    /** @return array<string, mixed> */
    public static function present(AuditEvent $event): array
    {
        $action = (string) $event->action;
        $meta = is_array($event->metadata) ? $event->metadata : [];
        [$system, $text, $href] = self::describe($action, $meta, $event);
        $actor = $event->relationLoaded('actor') ? $event->getRelation('actor') : null;

        return [
            'id' => (string) $event->getKey(),
            'actor' => $actor->name ?? $system,
            'system' => $actor === null,
            'text' => $text,
            'href' => $href,
            'action' => $action,
            'at' => $event->created_at->toAtomString(),
            'reference' => $event->correlation_id,
            'details' => collect($meta)
                ->filter(static fn (mixed $value): bool => is_scalar($value) || $value === null)
                ->map(static fn (mixed $value): string => match (true) {
                    $value === null => '—',
                    is_bool($value) => $value ? 'yes' : 'no',
                    default => Str::limit((string) $value, 120),
                })
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{0: string, 1: string, 2: string|null} who acted when no person did, what happened, where to look
     */
    private static function describe(string $action, array $meta, AuditEvent $event): array
    {
        $id = $event->auditable_id;
        $status = static fn (mixed $value): string => Str::lower(Str::headline((string) $value));
        $quoted = static fn (mixed $title): string => filled($title) ? ' “'.Str::limit((string) $title, 80).'”' : '';

        if (str_starts_with($action, 'website_content.')) {
            $collection = WebsiteCollections::find((string) ($meta['collection'] ?? ''));
            $item = 'the '.($collection['singular'] ?? 'listing').$quoted($meta['title'] ?? null);
            $href = $collection && $id ? route('admin.website.edit', ['collection' => $collection['key'], 'record' => $id], false) : null;
            $from = $meta['from_status'] ?? null;
            $to = $meta['to_status'] ?? null;
            $text = match (true) {
                $action === 'website_content.created' => "created {$item} as ".$status($to),
                $from !== $to && $to === 'published' => "published {$item}",
                $from !== $to => "changed {$item} to ".$status($to),
                default => "updated {$item}",
            };

            return ['Someone', $text, $href];
        }

        $contentHref = $id && $event->auditable_type === ContentItem::class ? route('admin.content.show', $id, false) : null;
        $pageHref = $id && $event->auditable_type === PageComposition::class ? route('admin.page-compositions.edit', $id, false) : null;
        $requestHref = $id && $event->auditable_type === EngagementSubmission::class ? route('admin.engagement.show', $id, false) : null;
        $applicationHref = $id && $event->auditable_type === Application::class ? route('admin.applications.show', $id, false) : null;

        if (str_starts_with($action, 'content.transition.')) {
            return ['Someone', 'moved a content draft to '.$status(Str::after($action, 'content.transition.')), $contentHref];
        }
        if (str_starts_with($action, 'settings.')) {
            return ['Someone', $action === 'settings.reset' ? 'reset settings to their defaults' : 'changed settings', route('admin.settings.edit', [], false)];
        }
        if (str_starts_with($action, 'seo.redirect')) {
            return ['Someone', 'changed a redirect', route('admin.seo.index', [], false)];
        }
        if (str_starts_with($action, 'retention.') || str_starts_with($action, 'privacy.')) {
            return ['Data retention', 'removed personal data that passed its retention period', null];
        }
        if (str_starts_with($action, 'newsletter.')) {
            return ['Newsletter', 'recorded a subscription change', null];
        }

        return match ($action) {
            'content.created' => ['Someone', 'created a content draft', $contentHref],
            'content.revision_created' => ['Someone', 'saved a new revision of a content draft', $contentHref],
            'content.rollback_revision_created' => ['Someone', 'restored an earlier version of a content draft', $contentHref],
            'content.published_by_schedule' => ['Scheduled publishing', 'put a content item on the website', $contentHref],
            'content.unpublished_by_schedule' => ['Scheduled publishing', 'took a content item off the website', $contentHref],
            'page_composition.draft_created' => ['Someone', 'started a new draft of a website page', $pageHref],
            'page_composition.transitioned' => ['Someone', 'moved a website page to '.$status($meta['to'] ?? 'a new step'), $pageHref],
            'engagement.received' => ['Website form', 'received a new '.$status($meta['type'] ?? 'contact').' request', $requestHref],
            'engagement.status_updated' => ['Someone', 'moved a request to '.$status($meta['to'] ?? 'a new status'), $requestHref],
            'application.received' => ['Careers page', 'received a new job application', $applicationHref],
            'application.status_updated' => ['Someone', 'moved an application to '.$status($meta['to'] ?? 'a new stage'), $applicationHref],
            'identity.user_invited' => ['Someone', 'invited a new team member', route('admin.users.index', [], false)],
            'identity.user_updated' => ['Someone', 'updated a team member’s access', route('admin.users.index', [], false)],
            'identity.role_updated' => ['Someone', 'changed what a role can do', route('admin.roles.index', [], false)],
            'identity.mfa_enabled' => ['Someone', 'turned on two-step verification', null],
            'identity.invitation_accepted' => ['Someone', 'accepted their invitation and signed in', null],
            'identity.account_self_deleted' => ['Someone', 'deleted their own account', null],
            'media.approved' => ['Someone', 'approved a file for the website', route('admin.media.index', [], false)],
            'media.details_updated' => ['Someone', 'updated a file’s title or description', route('admin.media.index', [], false)],
            'media.quarantined' => ['File scanner', 'is checking a newly uploaded file', route('admin.media.index', [], false)],
            'media.retention_deleted' => ['Data retention', 'removed an expired file', null],
            'navigation.updated' => ['Someone', 'updated the menus and footer', route('admin.navigation.edit', [], false)],
            'seo.audit.run' => ['Someone', 'ran an SEO check', route('admin.seo.index', [], false)],
            'seo.metadata.updated' => ['Someone', 'changed search settings for a page', route('admin.seo.index', [], false)],
            'private_file.downloaded' => ['Someone', 'downloaded a private file', null],
            'consent.changed' => ['Website visitor', 'changed their privacy choices', null],
            'event.registration.created' => ['Events page', 'received a new event registration', null],
            default => ['System', Str::lower(Str::headline(str_replace('.', ' ', $action))), null],
        };
    }
}
