<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Database\Seeders\LocaleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, LocaleSeeder::class]);
});

it('renders the permission-aware admin shell with active navigation and mobile drawer hooks', function (): void {
    $administrator = User::factory()->create();
    $administrator->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->get('/admin/content')
        ->assertOk()
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Admin/Content/Index')
            ->where('workspace.user.id', $administrator->id)
            ->where('workspace.privileged', true)
            ->where('workspace.permissions', fn ($permissions): bool => collect($permissions)->contains('content.view')));
});

it('keeps the admin shell backed by the approved semantic tokens only', function (): void {
    $css = File::get(resource_path('css/app.css'));
    $views = collect(File::allFiles(resource_path('js/Pages/Admin')))
        ->merge(File::allFiles(resource_path('js/Components/Workspace')))
        ->merge(File::allFiles(resource_path('js/Layouts')))
        ->map(fn (SplFileInfo $file): string => $file->getContents())
        ->implode("\n");

    foreach (['#17324d', '#2d6c99', '#2d7a78', '#c99a2e', '#1f2933', '#5d6a74', '#cdd5dc'] as $token) {
        expect(strtolower($css))->toContain($token);
    }

    expect($views)->not->toMatch('/#[0-9A-Fa-f]{3,8}/')
        ->and($views)->not->toContain('bg-[')
        ->and($views)->not->toContain('style="');
});

it('provides the shared React workspace controls', function (): void {
    $controls = File::get(resource_path('js/Components/Workspace/UI.jsx'));
    foreach (['Field', 'Editor', 'Action', 'Errors', 'Table', 'Pagination', 'Filters', 'Panel', 'Status'] as $control) {
        expect($controls)->toContain("export function {$control}(");
    }
    expect(File::exists(resource_path('js/Layouts/WorkspaceLayout.jsx')))->toBeTrue()
        ->and(File::exists(resource_path('js/Layouts/AuthLayout.jsx')))->toBeTrue();
});
