<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\AuditEventController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EngagementSubmissionController;
use App\Http\Controllers\Admin\ExpertController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NavigationController;
use App\Http\Controllers\Admin\PageComposerController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SettingHistoryController;
use App\Http\Controllers\Admin\SettingsDiagnosticsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserInvitationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'session.current', 'mfa'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/audit-events', [AuditEventController::class, 'index'])
        ->middleware('permission:audit.view')
        ->name('audit.index');
    Route::get('/media', [MediaController::class, 'index'])
        ->middleware('permission:media.view')
        ->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])
        ->middleware('permission:media.create')
        ->name('media.store');
    Route::post('/media/{media}/approve', [MediaController::class, 'approve'])
        ->middleware('permission:media.approve')
        ->name('media.approve');

    Route::resource('experts', ExpertController::class)
        ->except(['show'])
        ->middleware('permission:experts.manage');

    Route::get('/content', [ContentController::class, 'index'])
        ->middleware('permission:content.view')
        ->name('content.index');
    Route::get('/content/create', [ContentController::class, 'create'])
        ->middleware('permission:content.create')
        ->name('content.create');
    Route::post('/content', [ContentController::class, 'store'])
        ->middleware('permission:content.create')
        ->name('content.store');
    Route::get('/content/{content}/edit', [ContentController::class, 'edit'])
        ->middleware('permission:content.update')
        ->name('content.edit');
    Route::patch('/content/{content}', [ContentController::class, 'update'])
        ->middleware('permission:content.update')
        ->name('content.update');
    Route::get('/content/{content}/versions/{version}/preview', [ContentController::class, 'preview'])
        ->middleware('signed')
        ->name('content.preview');
    Route::post('/content/{content}/rollback', [ContentController::class, 'rollback'])
        ->middleware('permission:content.rollback')
        ->name('content.rollback');
    Route::get('/content/{content}', [ContentController::class, 'show'])
        ->middleware('permission:content.view')
        ->name('content.show');
    Route::post('/content/{content}/transitions', [ContentController::class, 'transition'])
        ->name('content.transition');

    Route::get('/page-compositions', [PageComposerController::class, 'index'])
        ->middleware('permission:pages.view')
        ->name('page-compositions.index');
    Route::get('/page-compositions/{composition}', [PageComposerController::class, 'edit'])
        ->middleware('permission:pages.view')
        ->name('page-compositions.edit');
    Route::get('/page-compositions/{composition}/preview', [PageComposerController::class, 'preview'])
        ->middleware(['signed', 'permission:pages.preview'])
        ->name('page-compositions.preview');
    Route::post('/page-compositions/{composition}/drafts', [PageComposerController::class, 'createDraft'])
        ->middleware('permission:pages.create')
        ->name('page-compositions.drafts.store');
    Route::post('/page-compositions/{composition}/transitions', [PageComposerController::class, 'transition'])
        ->name('page-compositions.transitions.store');
    Route::get('/navigation', [NavigationController::class, 'edit'])
        ->middleware('permission:navigation.manage')
        ->name('navigation.edit');
    Route::put('/navigation', [NavigationController::class, 'update'])
        ->middleware('permission:navigation.manage')
        ->name('navigation.update');
    Route::post('/page-compositions/{composition}/sections', [PageComposerController::class, 'storeSection'])
        ->middleware('permission:pages.update')
        ->name('page-compositions.sections.store');
    Route::patch('/page-compositions/{composition}/sections/{section}', [PageComposerController::class, 'updateSection'])
        ->middleware('permission:pages.update')
        ->name('page-compositions.sections.update');
    Route::put('/page-compositions/{composition}/sections/order', [PageComposerController::class, 'reorder'])
        ->middleware('permission:pages.update')
        ->name('page-compositions.sections.reorder');
    Route::post('/page-compositions/{composition}/sections/{section}/duplicate', [PageComposerController::class, 'duplicate'])
        ->middleware('permission:pages.update')
        ->name('page-compositions.sections.duplicate');
    Route::delete('/page-compositions/{composition}/sections/{section}', [PageComposerController::class, 'destroySection'])
        ->middleware('permission:pages.delete')
        ->name('page-compositions.sections.destroy');
    Route::post('/page-compositions/{composition}/sections/{section}/restore', [PageComposerController::class, 'restoreSection'])
        ->middleware('permission:pages.update')
        ->name('page-compositions.sections.restore');

    Route::get('/engagement', [EngagementSubmissionController::class, 'index'])
        ->middleware('permission:engagement.view')
        ->name('engagement.index');
    Route::get('/engagement/{submission}', [EngagementSubmissionController::class, 'show'])
        ->middleware('permission:engagement.view')
        ->name('engagement.show');
    Route::patch('/engagement/{submission}', [EngagementSubmissionController::class, 'update'])
        ->middleware('permission:engagement.update-status')
        ->name('engagement.update');

    Route::get('/applications', [ApplicationController::class, 'index'])
        ->middleware('permission:applications.view')
        ->name('applications.index');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])
        ->middleware('permission:applications.view')
        ->name('applications.show');
    Route::patch('/applications/{application}', [ApplicationController::class, 'update'])
        ->middleware('permission:applications.update-status')
        ->name('applications.update');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('users.index');
    Route::post('/users/invitations', [UserInvitationController::class, 'store'])
        ->middleware('permission:users.manage')
        ->name('users.invitations.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.manage')
        ->name('users.edit');
    Route::patch('/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.manage')
        ->name('users.update');

    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.manage')
        ->name('roles.index');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('permission:roles.manage')
        ->name('roles.edit');
    Route::patch('/roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.manage')
        ->name('roles.update');

    Route::get('/settings', [SettingController::class, 'index'])
        ->middleware('permission:settings.manage')
        ->name('settings.edit');
    Route::get('/settings/history', SettingHistoryController::class)
        ->middleware('permission:settings.manage')
        ->name('settings.history');
    Route::get('/settings/diagnostics', SettingsDiagnosticsController::class)
        ->middleware(['permission:settings.manage', 'recent_mfa:15'])
        ->name('settings.diagnostics');
    Route::post('/settings/diagnostics/run', [SettingsDiagnosticsController::class, 'run'])
        ->middleware(['permission:settings.manage', 'recent_mfa:15'])
        ->name('settings.diagnostics.run');
    Route::get('/settings/{category}', [SettingController::class, 'show'])
        ->middleware('permission:settings.manage')
        ->name('settings.show');
    Route::put('/settings/{category}', [SettingController::class, 'update'])
        ->middleware(['permission:settings.manage', 'recent_mfa:15'])
        ->name('settings.update');
    Route::post('/settings/{category}/reset', [SettingController::class, 'reset'])
        ->middleware(['permission:settings.manage', 'recent_mfa:15'])
        ->name('settings.reset');
});
