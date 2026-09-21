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
        ->assertSee('class="admin-shell"', false)
        ->assertSee('aria-label="Administration navigation"', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee('id="admin-mobile-navigation"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('x-bind:class="shellClass"', false)
        ->assertSee('Content workspace');
});

it('keeps the admin shell backed by the approved semantic tokens only', function (): void {
    $css = File::get(resource_path('css/app.css'));
    $views = collect(File::allFiles(resource_path('views/admin')))
        ->merge(File::allFiles(resource_path('views/components/admin')))
        ->merge(File::allFiles(resource_path('views/layouts')))
        ->map(fn (SplFileInfo $file): string => $file->getContents())
        ->implode("\n");

    foreach (['#17324d', '#2d6c99', '#2d7a78', '#c99a2e', '#1f2933', '#5d6a74', '#cdd5dc'] as $token) {
        expect(strtolower($css))->toContain($token);
    }

    expect($views)->not->toMatch('/#[0-9A-Fa-f]{3,8}/')
        ->and($views)->not->toContain('bg-[')
        ->and($views)->not->toContain('style="');
});

it('provides the required shared admin component inventory', function (): void {
    $required = [
        'alert',
        'attention-card',
        'audit-timeline',
        'bulk-action-bar',
        'button',
        'button-group',
        'card',
        'chart-card',
        'chart-data-summary',
        'chart-empty-state',
        'chart-error-state',
        'chart-header',
        'chart-legend',
        'chart-range-control',
        'chart-toolbar',
        'checkbox',
        'confirmation-modal',
        'content-container',
        'content-summary-card',
        'data-table',
        'date-input',
        'drawer',
        'empty-state',
        'error-summary',
        'field-error',
        'file-input',
        'filter-bar',
        'filter-chip',
        'filter-panel',
        'filter-sheet',
        'form-section',
        'help-text',
        'icon-button',
        'information-card',
        'input',
        'kpi-card',
        'mobile-record-list',
        'modal',
        'no-results-state',
        'pagination',
        'progress',
        'quick-action-card',
        'radio',
        'record-list',
        'row-action-menu',
        'search-input',
        'select',
        'skeleton',
        'status-card',
        'table-toolbar',
        'textarea',
        'toast',
        'workflow-panel',
        'workflow-rail',
    ];

    foreach ($required as $component) {
        expect(File::exists(resource_path("views/components/admin/{$component}.blade.php")))
            ->toBeTrue("Missing admin component: {$component}");
    }
});
