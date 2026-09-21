<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Enums\NewsletterStatus;
use App\Models\NewsletterSubscription;
use App\Models\SearchQueryLog;
use App\Models\UserInvitation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CleanupExpiredPrivacyDataCommand extends Command
{
    protected $signature = 'impact:privacy:cleanup-expired {--limit=500 : Maximum records per category}';

    protected $description = 'Remove expired pending tokens and privacy-minimized operational logs';

    public function handle(AuditRecorder $audit): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1 || $limit > 1000) {
            $this->error('The limit must be an integer between 1 and 1000.');

            return self::INVALID;
        }

        $counts = DB::transaction(function () use ($limit): array {
            $expiredInvitations = UserInvitation::query()
                ->with('user')
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '<=', now('UTC'))
                ->limit($limit)
                ->lockForUpdate()
                ->get();
            foreach ($expiredInvitations as $invitation) {
                $invitation->update(['revoked_at' => now('UTC')]);
                $invitation->user->roles()->detach();
                $invitation->user->increment('session_version');
            }

            $newsletterCutoff = now('UTC')
                ->subHours((int) config('impact.newsletter.confirmation_ttl_hours', 48));
            $newsletterDeleted = NewsletterSubscription::query()
                ->where('status', NewsletterStatus::Pending->value)
                ->where('confirmation_sent_at', '<=', $newsletterCutoff)
                ->limit($limit)
                ->delete();
            $queryLogDeleted = SearchQueryLog::query()
                ->where('created_at', '<=', now('UTC')->subDays(
                    (int) config('impact.privacy.search_query_log_days', 90),
                ))
                ->limit($limit)
                ->delete();
            $outboxDeleted = DB::table('analytics_event_outbox')
                ->where('status', 'delivered')
                ->where('delivered_at', '<=', now('UTC')->subDays(
                    (int) config('impact.privacy.analytics_outbox_days', 30),
                ))
                ->limit($limit)
                ->delete();

            return [
                'expired_invitations_revoked' => $expiredInvitations->count(),
                'pending_subscriptions_deleted' => $newsletterDeleted,
                'search_query_logs_deleted' => $queryLogDeleted,
                'analytics_outbox_deleted' => $outboxDeleted,
            ];
        }, attempts: 3);

        $audit->record(new AuditData(
            action: 'privacy.expired_data_cleaned',
            auditableType: self::class,
            auditableId: null,
            actorId: null,
            correlationId: (string) Str::uuid7(),
            metadata: $counts,
        ));

        $this->table(['Category', 'Processed'], collect($counts)
            ->map(fn (int $count, string $category): array => [$category, $count])
            ->values()
            ->all());

        return self::SUCCESS;
    }
}
