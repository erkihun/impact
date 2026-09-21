<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Access administration')"
            :title="__('Users')"
            :description="__('Review who can sign in, what each person is authorized to do and when they last used the platform.')"
        >
            <x-slot name="action">
                <a class="button-secondary" href="{{ route('admin.roles.index') }}">{{ __('Roles and permissions') }}</a>
            </x-slot>
        </x-admin.page-header>
    </x-slot>

    <div class="admin-workspace">
        {{-- Invitation is a secondary task: kept available but collapsed so the list stays the page purpose. --}}
        <details class="admin-panel" @if ($errors->hasAny(['name', 'email', 'roles', 'locale'])) open @endif>
            <summary class="flex min-h-11 cursor-pointer items-center justify-between gap-4 font-bold text-brand-950">
                <span>{{ __('Invite a staff user') }}</span>
                <span class="text-sm font-semibold text-action-700">{{ __('Open the invitation form') }}</span>
            </summary>

            <form class="mt-6 grid gap-5 md:grid-cols-2" method="POST" action="{{ route('admin.users.invitations.store') }}">
                @csrf
                <div>
                    <label class="form-label" for="invite-name">{{ __('Name') }}</label>
                    <input class="form-input" id="invite-name" name="name" required value="{{ old('name') }}" autocomplete="name">
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <div>
                    <label class="form-label" for="invite-email">{{ __('Email') }}</label>
                    <input class="form-input" id="invite-email" name="email" type="email" required value="{{ old('email') }}" autocomplete="email">
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>
                <div>
                    <label class="form-label" for="invite-locale">{{ __('Working language') }}</label>
                    <select class="form-input" id="invite-locale" name="locale">
                        @foreach ($locales as $locale)
                            <option value="{{ $locale }}" @selected(old('locale') === $locale)>{{ strtoupper($locale) }}</option>
                        @endforeach
                    </select>
                </div>
                <fieldset>
                    <legend class="form-label">{{ __('Roles') }}</legend>
                    <p class="mb-3 text-sm leading-6 text-muted">{{ __('Roles decide what this person can see and change. Assign the least access needed for their work.') }}</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($roles as $role)
                            <label class="flex min-h-11 items-center gap-2 text-sm">
                                <input class="rounded border-slate-400 text-action-500 focus:ring-knowledge-600" type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', []), true))>
                                <span>{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('roles')" />
                </fieldset>
                <div class="md:col-span-2">
                    <button class="button-primary" type="submit">{{ __('Send invitation') }}</button>
                </div>
            </form>
        </details>

        <div class="mt-8">
            <x-admin.filter-bar
                :legend="__('Filter users')"
                :has-active-filters="request()->filled('q') || request()->filled('status')"
                :clear-url="route('admin.users.index')"
            >
                <div>
                    <label class="form-label" for="user-search">{{ __('Search users') }}</label>
                    <input class="form-input" id="user-search" name="q" value="{{ request('q') }}" placeholder="{{ __('Name or email') }}" type="search">
                </div>
                <div>
                    <label class="form-label" for="user-status-filter">{{ __('Account status') }}</label>
                    <select class="form-input" id="user-status-filter" name="status">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\UserStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ __(str($status->value)->headline()->toString()) }}</option>
                        @endforeach
                    </select>
                </div>
            </x-admin.filter-bar>
        </div>

        <x-admin.result-summary :paginator="$users" :context="__('Authorized accounts')" />

        @if ($users->total() > 0)
            <div class="mt-5">
                <x-admin.record-list
                    :label="__('User accounts')"
                    :columns="[
                        ['label' => __('User')],
                        ['label' => __('Roles')],
                        ['label' => __('Status')],
                        ['label' => __('Last login')],
                        ['label' => __('Actions'), 'srOnly' => true],
                    ]"
                >
                    @foreach ($users as $user)
                        <tr>
                            <x-admin.cell :label="__('User')" primary>
                                {{ $user->name }}
                                <span class="mt-1 block text-sm font-normal text-muted">{{ $user->email }}</span>
                            </x-admin.cell>
                            <x-admin.cell :label="__('Roles')">
                                {{ $user->roles->pluck('name')->join(', ') ?: __('No role assigned') }}
                            </x-admin.cell>
                            <x-admin.cell :label="__('Status')"><x-ui.status-badge :status="$user->status" /></x-admin.cell>
                            <x-admin.cell :label="__('Last login')">
                                @if ($user->last_login_at)
                                    <time datetime="{{ $user->last_login_at->toIso8601String() }}">{{ $user->last_login_at->timezone(config('app.timezone'))->isoFormat('LLL') }}</time>
                                @else
                                    {{ __('Never') }}
                                @endif
                            </x-admin.cell>
                            <x-admin.row-actions :label="__('Actions')">
                                <a class="button-tertiary" href="{{ route('admin.users.edit', $user) }}">{{ __('Manage access') }} <span aria-hidden="true">→</span></a>
                            </x-admin.row-actions>
                        </tr>
                    @endforeach
                </x-admin.record-list>
            </div>
            <div class="mt-6">{{ $users->links() }}</div>
        @else
            <div class="mt-5">
                <x-ui.empty-state
                    :title="__('No users match these filters.')"
                    :description="__('Clear the filters to see every account, or invite a colleague using the form above.')"
                >
                    @if (request()->filled('q') || request()->filled('status'))
                        <a class="button-primary" href="{{ route('admin.users.index') }}">{{ __('Clear filters') }}</a>
                    @endif
                </x-ui.empty-state>
            </div>
        @endif
    </div>
</x-app-layout>
