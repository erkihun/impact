<?php

declare(strict_types=1);

use App\Http\Controllers\ApplicationFileDownloadController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MediaDownloadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\ApplicationController;
use App\Http\Controllers\Public\CaseStudyController;
use App\Http\Controllers\Public\ConsentController;
use App\Http\Controllers\Public\EngagementSubmissionController;
use App\Http\Controllers\Public\EventController;
use App\Http\Controllers\Public\EventRegistrationController;
use App\Http\Controllers\Public\ExpertController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\IndustryController;
use App\Http\Controllers\Public\InsightController;
use App\Http\Controllers\Public\NewsletterSubscriptionController;
use App\Http\Controllers\Public\RedirectController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\ServiceController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\SystemStatusController;
use App\Http\Controllers\Public\VacancyController;
use App\Http\Controllers\SubmissionFileDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/ready', [HealthController::class, 'ready'])->name('ready');
Route::post('/consent', [ConsentController::class, 'store'])->name('consent.update');
Route::get('/newsletter/confirm/{token}', [NewsletterSubscriptionController::class, 'confirm'])
    ->middleware(['signed', 'throttle:public-forms'])
    ->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterSubscriptionController::class, 'unsubscribe'])
    ->middleware(['signed', 'throttle:public-forms'])
    ->name('newsletter.unsubscribe');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemaps/{locale}.xml', [SitemapController::class, 'locale'])
    ->where(['locale' => 'en|am'])
    ->name('sitemap.locale');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/status', SystemStatusController::class)
    ->middleware('setting.enabled:maintenance.allow_status_page')
    ->name('system-status');

Route::prefix('{locale}')
    ->where(['locale' => 'en|am'])
    ->group(function (): void {
        Route::get('/', HomeController::class)->name('localized-home');
        Route::view('/about', 'public.about')->name('about.show');
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show');
        Route::get('/industries', [IndustryController::class, 'index'])->name('industries.index');
        Route::get('/industries/{slug}', [IndustryController::class, 'show'])->name('industries.show');
        Route::get('/experts', [ExpertController::class, 'index'])->name('experts.index');
        Route::get('/experts/{slug}', [ExpertController::class, 'show'])->name('experts.show');
        Route::get('/case-studies', [CaseStudyController::class, 'index'])->name('case-studies.index');
        Route::get('/case-studies/{slug}', [CaseStudyController::class, 'show'])->name('case-studies.show');
        Route::get('/insights', [InsightController::class, 'index'])->name('insights.index');
        Route::get('/insights/{slug}', [InsightController::class, 'show'])->name('insights.show');
        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/{slug}', [EventController::class, 'show'])->name('events.show');
        Route::post('/events/{slug}/registrations', [EventRegistrationController::class, 'store'])
            ->middleware('throttle:public-forms')
            ->name('events.registrations.store');
        Route::get('/careers', [VacancyController::class, 'index'])->name('careers.index');
        Route::get('/careers/{slug}', [VacancyController::class, 'show'])->name('careers.show');
        Route::post('/careers/{slug}/applications', [ApplicationController::class, 'store'])
            ->middleware('throttle:public-forms')
            ->name('careers.applications.store');
        Route::get('/search', SearchController::class)
            ->middleware(['setting.enabled:search.enabled', 'throttle:search'])
            ->name('search');
        Route::view('/consultation', 'public.consultation')
            ->middleware('setting.enabled:engagement.consultation_form_enabled')
            ->name('consultation.create');
        Route::view('/request-for-proposal', 'public.rfp')
            ->middleware('setting.enabled:engagement.rfp_form_enabled')
            ->name('rfp.create');
        Route::view('/contact', 'public.contact')
            ->middleware('setting.enabled:engagement.contact_form_enabled')
            ->name('contact.create');
        Route::view('/privacy', 'public.legal.privacy')->name('legal.privacy');
        Route::view('/terms', 'public.legal.terms')->name('legal.terms');
        Route::view('/cookies', 'public.legal.cookies')->name('legal.cookies');
        Route::view('/accessibility', 'public.legal.accessibility')->name('legal.accessibility');

        Route::middleware('throttle:public-forms')->group(function (): void {
            Route::post('/consultation-requests', [EngagementSubmissionController::class, 'store'])
                ->middleware('setting.enabled:engagement.consultation_form_enabled')
                ->name('consultation-requests.store');
            Route::post('/rfp-requests', [EngagementSubmissionController::class, 'store'])
                ->middleware('setting.enabled:engagement.rfp_form_enabled')
                ->name('rfp-requests.store');
            Route::post('/contact', [EngagementSubmissionController::class, 'store'])
                ->middleware('setting.enabled:engagement.contact_form_enabled')
                ->name('contact.store');
            Route::post('/newsletter-subscriptions', [NewsletterSubscriptionController::class, 'store'])
                ->name('newsletter.subscribe');
        });
    });

Route::middleware(['auth', 'session.current'])->group(function (): void {
    Route::get('/restricted-media/{media}/download', MediaDownloadController::class)
        ->middleware('signed')
        ->name('media.download');
    Route::get('/application-files/{file}/download', ApplicationFileDownloadController::class)
        ->middleware('signed')
        ->name('application-files.download');
    Route::get('/submission-files/{file}/download', SubmissionFileDownloadController::class)
        ->middleware('signed')
        ->name('submission-files.download');
    Route::view('/dashboard', 'dashboard')->middleware('verified')->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::fallback(RedirectController::class);
