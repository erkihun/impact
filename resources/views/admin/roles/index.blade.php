<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Access administration')"
            :title="__('Roles and permissions')"
            :description="__('A role is a named set of permissions. Assign people the role that matches their work, and nothing wider.')"
        >
            <x-slot name="action">
                <a class="button-secondary" href="{{ route('admin.users.index') }}">{{ __('Users') }}</a>
            </x-slot>
        </x-admin.page-header>
    </x-slot>

    <div class="admin-workspace">
        <div class="grid gap-5 lg:grid-cols-2">
            @foreach ($roles as $role)
                <article class="admin-panel">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="font-editorial text-lg font-bold text-brand-950">{{ $role->name }}</h2>
                            <p class="mt-1 text-sm text-muted">
                                {{ trans_choice(':count assigned user|:count assigned users', $role->users_count, ['count' => $role->users_count]) }}
                            </p>
                        </div>
                        <a class="button-tertiary" href="{{ route('admin.roles.edit', $role) }}">
                            {{ __('Manage role') }} <span aria-hidden="true">→</span>
                        </a>
                    </div>

                    {{-- Permissions are described in business language rather than as raw codes. --}}
                    @if ($role->permissions->isNotEmpty())
                        <div class="mt-5 border-t border-slate-200 pt-4">
                            <h3 class="text-xs font-black uppercase tracking-[0.12em] text-muted">
                                {{ trans_choice('This role allows :count action|This role allows :count actions', $role->permissions->count(), ['count' => $role->permissions->count()]) }}
                            </h3>
                            <ul class="mt-3 grid gap-2">
                                @foreach ($role->permissions->take(6) as $permission)
                                    <li class="flex gap-2.5 text-sm leading-6 text-slate-700">
                                        <span class="mt-2 size-1.5 shrink-0 bg-action-500" aria-hidden="true"></span>
                                        <span>{{ $permission->description ?: $permission->code }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($role->permissions->count() > 6)
                                <p class="mt-3 text-sm text-muted">
                                    {{ trans_choice('and :count further action|and :count further actions', $role->permissions->count() - 6, ['count' => $role->permissions->count() - 6]) }}
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="mt-5 border-t border-slate-200 pt-4 text-sm text-muted">{{ __('This role has no permissions assigned yet.') }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</x-app-layout>
