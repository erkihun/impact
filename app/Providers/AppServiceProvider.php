<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AuditRecorder;
use App\Contracts\Clock;
use App\Contracts\MalwareScanner;
use App\Contracts\PublicReferenceGenerator;
use App\Contracts\SearchIndexer;
use App\Models\User;
use App\Services\DatabaseAuditRecorder;
use App\Services\Media\ClamAvMalwareScanner;
use App\Services\PageComposer;
use App\Services\PublicNavigation;
use App\Services\Search\DatabaseSearchIndexer;
use App\Services\SecurePublicReferenceGenerator;
use App\Services\SystemClock;
use App\Support\CorrelationContext;
use App\Support\Settings\PublicUiSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CorrelationContext::class);
        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(AuditRecorder::class, DatabaseAuditRecorder::class);
        $this->app->bind(MalwareScanner::class, ClamAvMalwareScanner::class);
        $this->app->bind(PublicReferenceGenerator::class, SecurePublicReferenceGenerator::class);
        $this->app->bind(SearchIndexer::class, DatabaseSearchIndexer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());
        DB::prohibitDestructiveCommands(app()->isProduction());

        RateLimiter::for('public-forms', static function (Request $request): array {
            $identity = hash('sha256', (string) $request->ip());

            return [
                Limit::perMinute(10)->by($identity),
                Limit::perDay(100)->by($identity),
            ];
        });

        RateLimiter::for(
            'search',
            static fn (Request $request): Limit => Limit::perMinute(60)
                ->by(hash('sha256', (string) $request->ip())),
        );

        View::composer('layouts.navigation', static function ($view): void {
            $user = auth()->user();
            if (! $user instanceof User) {
                $view->with('adminNavigation', []);

                return;
            }
            $user->loadMissing('roles.permissions');
            $view->with('adminNavigation', [
                'administration' => $user->isPrivileged(),
                'content' => $user->hasPermission('content.view'),
                'experts' => $user->hasPermission('experts.manage'),
                'pages' => $user->hasPermission('pages.view'),
                'navigation' => $user->hasPermission('navigation.manage'),
                'engagement' => $user->hasPermission('engagement.view'),
                'applications' => $user->hasPermission('applications.view'),
                'media' => $user->hasPermission('media.view'),
                'users' => $user->hasPermission('users.view'),
                'roles' => $user->hasPermission('roles.manage'),
                'settings' => $user->hasPermission('settings.manage'),
                'audit' => $user->hasPermission('audit.view'),
            ]);
        });

        View::composer(
            [
                'layouts.public',
                'public.home',
                'public.about',
                'public.collection',
                'public.detail',
                'public.event',
                'public.vacancy',
                'public.consultation',
                'public.rfp',
                'public.contact',
                'public.search',
                'public.composed-page',
                'public.legal.*',
            ],
            static function ($view): void {
                $routeName = request()->route()?->getName();
                $pageKey = match ($routeName) {
                    'home', 'localized-home' => 'home',
                    'about.show' => 'about',
                    'consultation.create' => 'consultation',
                    'rfp.create' => 'rfp',
                    'contact.create' => 'contact',
                    'legal.privacy' => 'legal.privacy',
                    'legal.terms' => 'legal.terms',
                    'legal.cookies' => 'legal.cookies',
                    'legal.accessibility' => 'legal.accessibility',
                    default => is_string($routeName) && (
                        str_ends_with($routeName, '.index')
                        || str_ends_with($routeName, '.show')
                        || $routeName === 'search'
                    ) ? $routeName : null,
                };

                $view->with([
                    'publicExperience' => app(PublicUiSettings::class)->viewData(),
                    'managedComposition' => $pageKey === null
                        ? null
                        : app(PageComposer::class)->published($pageKey, app()->getLocale()),
                    'managedNavigation' => collect([
                        'primary', 'footer_explore', 'footer_engage', 'footer_legal',
                    ])->mapWithKeys(fn (string $location): array => [
                        $location => app(PublicNavigation::class)->location($location, app()->getLocale()),
                    ]),
                ]);
            },
        );
    }
}
