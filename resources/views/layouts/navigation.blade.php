@php
    // Navigation is declared once and rendered in both the desktop rail and the
    // mobile drawer. Visibility comes from backend permissions only; a group
    // with no visible items is never rendered.
    $navigationGroups = [
        [
            'label' => __('Overview'),
            'items' => [
                ['visible' => $adminNavigation['administration'] ?? false, 'label' => __('Operations dashboard'), 'icon' => 'dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
            ],
        ],
        [
            'label' => __('Content'),
            'items' => [
                ['visible' => $adminNavigation['content'] ?? false, 'label' => __('Content'), 'icon' => 'content', 'route' => 'admin.content.index', 'active' => 'admin.content.*'],
                ['visible' => $adminNavigation['experts'] ?? false, 'label' => __('Experts'), 'icon' => 'users', 'route' => 'admin.experts.index', 'active' => 'admin.experts.*'],
                ['visible' => $adminNavigation['pages'] ?? false, 'label' => __('Page composer'), 'icon' => 'pages', 'route' => 'admin.page-compositions.index', 'active' => 'admin.page-compositions.*'],
                ['visible' => $adminNavigation['navigation'] ?? false, 'label' => __('Navigation and footer'), 'icon' => 'navigation', 'route' => 'admin.navigation.edit', 'active' => 'admin.navigation.*'],
                ['visible' => $adminNavigation['media'] ?? false, 'label' => __('Media library'), 'icon' => 'media', 'route' => 'admin.media.index', 'active' => 'admin.media.*'],
            ],
        ],
        [
            'label' => __('Engagement'),
            'items' => [
                ['visible' => $adminNavigation['engagement'] ?? false, 'label' => __('Engagement inbox'), 'icon' => 'engagement', 'route' => 'admin.engagement.index', 'active' => 'admin.engagement.*'],
                ['visible' => $adminNavigation['applications'] ?? false, 'label' => __('Applications'), 'icon' => 'applications', 'route' => 'admin.applications.index', 'active' => 'admin.applications.*'],
            ],
        ],
        [
            'label' => __('People and access'),
            'items' => [
                ['visible' => $adminNavigation['users'] ?? false, 'label' => __('Users'), 'icon' => 'users', 'route' => 'admin.users.index', 'active' => 'admin.users.*'],
                ['visible' => $adminNavigation['roles'] ?? false, 'label' => __('Roles and permissions'), 'icon' => 'roles', 'route' => 'admin.roles.index', 'active' => 'admin.roles.*'],
            ],
        ],
        [
            'label' => __('Operations'),
            'items' => [
                ['visible' => $adminNavigation['audit'] ?? false, 'label' => __('Audit events'), 'icon' => 'audit', 'route' => 'admin.audit.index', 'active' => 'admin.audit.*'],
                ['visible' => $adminNavigation['settings'] ?? false, 'label' => __('System settings'), 'icon' => 'settings', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*'],
            ],
        ],
    ];

    $visibleGroups = collect($navigationGroups)
        ->map(fn (array $group): array => [
            'label' => $group['label'],
            'items' => collect($group['items'])->filter(fn (array $item): bool => (bool) $item['visible'])->values()->all(),
        ])
        ->filter(fn (array $group): bool => count($group['items']) > 0)
        ->values();

    $currentLocale = app()->getLocale();
    $alternateLocale = $currentLocale === 'en' ? 'am' : 'en';
    $alternateUrl = url('/'.$alternateLocale);
    $effectiveSettings = app(\App\Support\Settings\EffectiveSettings::class);
    $identity = app(\App\Support\Settings\PublicUiSettings::class)->identity();
    $adminBrand = (string) $effectiveSettings->effective('site.short_name');
    $adminBrandFull = (string) $effectiveSettings->effective('site.name');
@endphp

<div>
    <aside
        class="admin-sidebar hidden lg:flex"
        aria-label="{{ __('Administration navigation') }}"
    >
        <div class="admin-sidebar-header">
            @if ($identity['display_admin_logo'] && $identity['logo'])
                <img class="h-10 w-10 shrink-0 rounded bg-white object-contain p-1" src="{{ $identity['logo'] }}" alt="{{ $identity['logo_alt'] }}">
            @else
                <span class="brand-mark shrink-0" aria-hidden="true">I</span>
            @endif
            <span class="admin-sidebar-identity" x-show="expanded" x-cloak>
                <span class="block font-extrabold leading-none text-white">{{ $adminBrand }}</span>
                <span class="mt-1 block text-[0.65rem] uppercase tracking-[0.16em] text-white/60">{{ __('Administration') }}</span>
            </span>
            <button
                type="button"
                class="admin-sidebar-collapse"
                x-on:click="toggleRail"
                x-bind:aria-expanded="expanded"
                aria-controls="admin-sidebar-nav"
                x-bind:aria-label="collapseLabel"
                data-label-collapse="{{ __('Collapse navigation') }}"
                data-label-expand="{{ __('Expand navigation') }}"
            >
                <x-admin.icon name="collapse" class="size-4" x-bind:class="collapseIconClass" />
            </button>
        </div>

        <nav id="admin-sidebar-nav" class="flex-1 overflow-y-auto px-3 py-5">
            @foreach ($visibleGroups as $group)
                <div @class(['mt-5' => ! $loop->first])>
                    <p class="admin-sidebar-group-label" x-show="expanded" x-cloak>{{ $group['label'] }}</p>
                    <div class="mt-2 grid gap-1">
                        @foreach ($group['items'] as $item)
                            <a
                                class="admin-nav-link"
                                href="{{ route($item['route']) }}"
                                @if (request()->routeIs($item['active'])) aria-current="page" @endif
                                title="{{ $item['label'] }}"
                            >
                                {{-- Active state carries a marker bar plus a background,
                                     so it never depends on colour alone. --}}
                                <span class="admin-nav-marker" aria-hidden="true"></span>
                                <x-admin.icon :name="$item['icon']" class="size-5 shrink-0" />
                                <span class="admin-nav-label" x-show="expanded" x-cloak>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-3">
            <a class="admin-nav-link" href="{{ route('profile.edit') }}" @if (request()->routeIs('profile.*')) aria-current="page" @endif title="{{ __('Profile and security') }}">
                <span class="admin-nav-marker" aria-hidden="true"></span>
                <x-admin.icon name="profile" class="size-5 shrink-0" />
                <span class="admin-nav-label" x-show="expanded" x-cloak>{{ __('Profile and security') }}</span>
            </a>
            <a class="admin-nav-link" href="{{ route('localized-home', ['locale' => $currentLocale]) }}" title="{{ __('View public website') }}">
                <span class="admin-nav-marker" aria-hidden="true"></span>
                <x-admin.icon name="external" class="size-5 shrink-0" />
                <span class="admin-nav-label" x-show="expanded" x-cloak>{{ __('View public website') }}</span>
            </a>
        </div>
    </aside>

    {{-- Mobile drawer: focus-trapped, Escape-closable, scroll-locked. --}}
    <div
        id="admin-mobile-navigation"
        class="admin-drawer lg:hidden"
        x-cloak
        x-show="mobileOpen"
        x-transition.opacity
        x-ref="mobileSheet"
        x-on:keydown="trapFocus"
        role="dialog"
        aria-modal="true"
        aria-labelledby="admin-mobile-navigation-title"
    >
        <div class="flex min-h-16 items-center justify-between gap-3 border-b border-edge px-4">
            <div class="flex items-center gap-2.5">
                <span class="brand-mark size-9 text-base" aria-hidden="true">I</span>
                <p id="admin-mobile-navigation-title" class="font-bold text-brand-900">{{ $adminBrandFull }} {{ __('Administration') }}</p>
            </div>
            <button class="icon-button" type="button" x-ref="mobileClose" x-on:click="closeMobile" aria-label="{{ __('Close menu') }}">
                <x-admin.icon name="close" class="size-6" />
            </button>
        </div>

        <nav class="overflow-y-auto p-4" aria-label="{{ __('Administration navigation') }}">
            @foreach ($visibleGroups as $group)
                <div @class(['mt-5' => ! $loop->first])>
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-muted">{{ $group['label'] }}</p>
                    <div class="mt-2 grid gap-1">
                        @foreach ($group['items'] as $item)
                            <a class="mobile-link gap-3" href="{{ route($item['route']) }}" @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                                <x-admin.icon :name="$item['icon']" class="size-5 shrink-0" />
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="my-4 border-t border-edge"></div>
            <a class="mobile-link gap-3" href="{{ route('profile.edit') }}">
                <x-admin.icon name="profile" class="size-5 shrink-0" />{{ __('Profile and security') }}
            </a>
            <a class="mobile-link gap-3" href="{{ $alternateUrl }}" hreflang="{{ $alternateLocale }}" lang="{{ $alternateLocale }}">
                <x-admin.icon name="external" class="size-5 shrink-0" />{{ $alternateLocale === 'am' ? 'አማርኛ' : 'English' }}
            </a>
            <a class="mobile-link gap-3" href="{{ route('localized-home', ['locale' => $currentLocale]) }}">
                <x-admin.icon name="external" class="size-5 shrink-0" />{{ __('View public website') }}
            </a>
        </nav>
    </div>

    <div class="admin-topbar">
        <div class="flex min-w-0 items-center gap-3">
            <button class="icon-button lg:hidden" type="button" x-on:click="openMobile" aria-controls="admin-mobile-navigation" x-bind:aria-expanded="mobileOpen" aria-label="{{ __('Open menu') }}">
                <x-admin.icon name="menu" class="size-6" />
            </button>
            <p class="hidden truncate text-sm font-semibold text-muted sm:block">{{ __('Secure editorial workspace') }}</p>
        </div>

        <div class="flex items-center gap-2">
            {{-- Language is a labelled control, never a flag. --}}
            <a
                class="hidden min-h-11 items-center rounded-lg border border-edge px-3 text-xs font-bold uppercase text-brand-900 hover:border-action-500 hover:text-action-700 sm:inline-flex"
                href="{{ $alternateUrl }}"
                hreflang="{{ $alternateLocale }}"
                lang="{{ $alternateLocale }}"
            >{{ $alternateLocale }}</a>

            <a class="button-tertiary hidden sm:inline-flex" href="{{ route('localized-home', ['locale' => $currentLocale]) }}">{{ __('View website') }}</a>

            <x-dropdown align="right" width="56">
                <x-slot name="trigger">
                    <button class="admin-user-trigger" type="button">
                        <span class="admin-user-avatar" aria-hidden="true">{{ str(Auth::user()->name)->substr(0, 1)->upper() }}</span>
                        <span class="hidden max-w-40 truncate sm:block">{{ Auth::user()->name }}</span>
                        <span aria-hidden="true">⌄</span>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <div class="border-b border-edge px-4 py-3">
                        <p class="truncate text-sm font-bold text-brand-900">{{ Auth::user()->name }}</p>
                        <p class="truncate text-xs text-muted">{{ Auth::user()->email }}</p>
                    </div>
                    <x-dropdown-link :href="route('profile.edit')">{{ __('Profile and security') }}</x-dropdown-link>
                    <x-dropdown-link :href="$alternateUrl">{{ $alternateLocale === 'am' ? 'አማርኛ' : 'English' }}</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" data-logout>{{ __('Sign out') }}</x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</div>
