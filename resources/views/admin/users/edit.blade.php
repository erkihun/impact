<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Access administration')"
            :title="$managedUser->name"
            :description="__('Change what this person can reach. Saving also signs them out of every active session.')"
        >
            <x-slot name="action">
                <a class="button-secondary" href="{{ route('admin.users.index') }}">{{ __('Back to users') }}</a>
            </x-slot>
        </x-admin.page-header>
    </x-slot>

    <div class="admin-workspace">
        @if (session('status'))
            <div class="status-success mb-6" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-summary mb-6" data-error-summary tabindex="-1" role="alert">
                <p class="font-bold">{{ __('This account was not updated.') }}</p>
                <ul class="mt-2 grid gap-1">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="max-w-3xl" data-prevent-duplicate>
            @csrf
            @method('PATCH')

            <div class="admin-panel grid gap-10">
                <x-admin.section
                    :title="__('Identity')"
                    :description="__('How this person is named and how they sign in.')"
                    class="border-t-0 pt-0"
                >
                    <div class="grid gap-5">
                        <div>
                            <label class="form-label" for="name">{{ __('Name') }}</label>
                            <input class="form-input" id="name" name="name" value="{{ old('name', $managedUser->name) }}" required autocomplete="name">
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <label class="form-label" for="email">{{ __('Email address') }}</label>
                            <input class="form-input" id="email" name="email" type="email" value="{{ old('email', $managedUser->email) }}" required autocomplete="email">
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>
                    </div>
                </x-admin.section>

                <x-admin.section
                    :title="__('Account state')"
                    :description="__('Suspending or expiring an account prevents sign-in without deleting any history.')"
                >
                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label class="form-label" for="locale">{{ __('Working language') }}</label>
                            <select class="form-input" id="locale" name="locale">
                                <option value="en" @selected(old('locale', $managedUser->locale) === 'en')>English</option>
                                <option value="am" @selected(old('locale', $managedUser->locale) === 'am')>አማርኛ</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="status">{{ __('Status') }}</label>
                            <select class="form-input" id="status" name="status">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', $managedUser->status->value) === $status->value)>{{ __(str($status->value)->headline()->toString()) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="expires_at">{{ __('Access expires') }}</label>
                            <input class="form-input" id="expires_at" name="expires_at" type="date" value="{{ old('expires_at', $managedUser->expires_at?->format('Y-m-d')) }}" aria-describedby="expires-help">
                            <p class="form-help" id="expires-help">{{ __('Leave empty for no expiry.') }}</p>
                        </div>
                    </div>
                </x-admin.section>

                <x-admin.section
                    :title="__('Roles')"
                    :description="__('Roles decide what this person can see and change. Assign the least access needed for their work.')"
                >
                    <fieldset>
                        <legend class="sr-only">{{ __('Roles') }}</legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($roles as $role)
                                <label class="flex min-h-11 items-center gap-3 rounded-lg border border-slate-300 p-3 text-sm">
                                    <input
                                        class="rounded border-slate-400 text-action-500 focus:ring-knowledge-600"
                                        type="checkbox"
                                        name="roles[]"
                                        value="{{ $role->id }}"
                                        @checked(in_array($role->id, old('roles', $managedUser->roles->pluck('id')->all()), true))
                                    >
                                    <span>{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('roles')" />
                    </fieldset>
                </x-admin.section>
            </div>

            <div class="mt-6 grid gap-3">
                <p class="text-sm leading-6 text-muted">{{ __('Saving revokes every active session for this account. They will need to sign in again.') }}</p>
                <div><button class="button-primary" type="submit">{{ __('Save changes and revoke sessions') }}</button></div>
            </div>
        </form>
    </div>
</x-app-layout>
