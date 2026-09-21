<?php

declare(strict_types=1);

use App\Models\NewsletterSubscription;
use App\Models\Role;
use App\Models\SearchQueryLog;
use App\Models\User;
use App\Models\UserInvitation;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

it('cleans bounded expired tokens and minimized operational logs with audit evidence', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    $inviter = User::factory()->create();
    $invitee = User::factory()->create(['status' => 'invited']);
    $invitee->roles()->attach(Role::query()->where('code', 'editor')->sole());
    UserInvitation::query()->create([
        'user_id' => $invitee->id,
        'invited_by' => $inviter->id,
        'token_hash' => hash('sha256', 'expired-invitation'),
        'role_ids' => $invitee->roles()->pluck('roles.id')->all(),
        'locale' => 'en',
        'expires_at' => now('UTC')->subDay(),
    ]);
    NewsletterSubscription::query()->create([
        'email' => 'unconfirmed@example.test',
        'normalized_email' => 'unconfirmed@example.test',
        'locale' => 'en',
        'policy_version' => config('impact.privacy.policy_version'),
        'status' => 'pending',
        'confirmation_token_hash' => hash('sha256', 'expired-confirmation'),
        'confirmation_sent_at' => now('UTC')->subWeek(),
    ]);
    SearchQueryLog::query()->create([
        'locale' => 'en',
        'query_hash' => hash('sha256', 'old query'),
        'result_count' => 1,
        'created_at' => now('UTC')->subDays(100),
    ]);
    DB::table('analytics_event_outbox')->insert([
        'id' => (string) str()->uuid7(),
        'event' => 'page_view',
        'payload' => '{}',
        'status' => 'delivered',
        'attempts' => 1,
        'available_at' => now('UTC')->subDays(40),
        'delivered_at' => now('UTC')->subDays(40),
        'created_at' => now('UTC')->subDays(40),
        'updated_at' => now('UTC')->subDays(40),
    ]);

    $this->artisan('impact:privacy:cleanup-expired', ['--limit' => 100])->assertSuccessful();

    expect(UserInvitation::query()->sole()->revoked_at)->not->toBeNull()
        ->and($invitee->roles()->count())->toBe(0)
        ->and(NewsletterSubscription::query()->count())->toBe(0)
        ->and(SearchQueryLog::query()->count())->toBe(0)
        ->and(DB::table('analytics_event_outbox')->count())->toBe(0);
    $this->assertDatabaseHas('audit_events', ['action' => 'privacy.expired_data_cleaned']);
});
