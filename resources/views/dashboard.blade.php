<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">{{ __('Account') }}</p>
            <h1 class="mt-2 text-2xl font-extrabold text-brand-900">{{ __('Secure workspace') }}</h1>
        </div>
    </x-slot>

    <div class="admin-workspace">
        <section class="admin-panel max-w-3xl">
            <h2 class="text-xl font-bold text-brand-900">{{ __('You are signed in.') }}</h2>
            <p class="mt-3 text-sm leading-7 text-muted">{{ __('Use the permission-aware navigation to open your assigned administration workspaces, or review your profile and security settings.') }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                @if (Auth::user()->isPrivileged())
                    <a class="button-primary" href="{{ route('admin.dashboard') }}">{{ __('Open operations dashboard') }} →</a>
                @endif
                <a class="button-secondary" href="{{ route('profile.edit') }}">{{ __('Profile and security') }}</a>
            </div>
        </section>
    </div>
</x-app-layout>
