<?php

declare(strict_types=1);

use App\Exceptions\ContentVersionConflictException;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\LocaleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, LocaleSeeder::class]);
});

it('serves the protected workspace as React pages without exposing account secrets', function (): void {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());
    $this->actingAs($admin)->withSession(privilegedSession($admin));

    foreach ([
        '/admin' => 'Admin/Dashboard',
        '/admin/content' => 'Admin/Content/Index',
        '/admin/content/create' => 'Admin/Content/Create',
        '/admin/experts' => 'Admin/Experts/Index',
        '/admin/experts/create' => 'Admin/Experts/Create',
        '/admin/page-compositions' => 'Admin/PageCompositions/Index',
        '/admin/navigation' => 'Admin/Navigation/Edit',
        '/admin/engagement' => 'Admin/Engagement/Index',
        '/admin/applications' => 'Admin/Applications/Index',
        '/admin/media' => 'Admin/Media/Index',
        '/admin/users' => 'Admin/Users/Index',
        '/admin/roles' => 'Admin/Roles/Index',
        '/admin/settings' => 'Admin/Settings/Index',
        '/admin/settings/security' => 'Admin/Settings/Show',
        '/admin/settings/history' => 'Admin/Settings/History',
        '/admin/settings/diagnostics' => 'Admin/Settings/Diagnostics',
        '/admin/audit-events' => 'Admin/Audit/Index',
        '/profile' => 'Profile/Edit',
    ] as $url => $component) {
        $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->where('workspace.user.id', $admin->id)
            ->missing('workspace.user.password')
            ->missing('workspace.user.mfa_secret')
            ->missing('workspace.user.mfa_recovery_codes')
            ->where('meta.robots', 'noindex,nofollow,noarchive'));
    }

    $this->get('/admin/users/'.$admin->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Users/Edit')
        ->missing('managedUser.password')
        ->missing('managedUser.remember_token')
        ->missing('managedUser.mfa_secret'));
});

it('keeps unauthorized users out of React administration', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(privilegedSession($user))
        ->get('/admin/users')->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 403));
});

it('returns React conflicts to Inertia and preserves the JSON API contract', function (): void {
    Route::middleware('web')->post('/react-conflict-test', fn () => throw new ContentVersionConflictException('old', 'current'));
    $this->post('/react-conflict-test', [], ['X-Inertia' => 'true', 'Accept' => 'text/html'])
        ->assertConflict()->assertJsonPath('component', 'Error')
        ->assertJsonPath('props.code', 'CONTENT_VERSION_CONFLICT')
        ->assertJsonPath('props.current_version_id', 'current');
    $this->postJson('/react-conflict-test')->assertConflict()
        ->assertJsonPath('code', 'CONTENT_VERSION_CONFLICT')->assertJsonMissingPath('component');
});

it('returns a React not-found page and retains its HTTP status', function (): void {
    $this->get('/page-that-does-not-exist')->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));
});

it('inserts the public SSR body and head but never sends authentication pages to the renderer', function (): void {
    config(['inertia.ssr.enabled' => true, 'inertia.ssr.ensure_bundle_exists' => false]);
    Http::fake(['*' => Http::response([
        'head' => ['<title data-inertia="">Rendered public page</title>'],
        'body' => '<div id="app"><h1>Server rendered public content</h1></div>',
    ])]);
    $this->get('/')->assertOk()->assertSee('<h1>Server rendered public content</h1>', false)
        ->assertSee('<title data-inertia="">Rendered public page</title>', false);
    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => str_starts_with($request['component'], 'Public/') && $request['props']['workspace'] === null);
    $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    Http::assertSentCount(1);

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());
    $this->actingAs($admin)->withSession(privilegedSession($admin))->get('/admin/users')->assertOk();
    Http::assertSentCount(1);
});
