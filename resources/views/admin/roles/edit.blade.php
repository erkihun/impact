<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Access administration')"
            :title="$managedRole->name"
            :description="__('Choose what everyone holding this role is allowed to do. Saving signs out every affected person.')"
        >
            <x-slot name="action">
                <a class="button-secondary" href="{{ route('admin.roles.index') }}">{{ __('Back to roles') }}</a>
            </x-slot>
        </x-admin.page-header>
    </x-slot>

    <div class="admin-workspace">
        @if (session('status'))
            <div class="status-success mb-6" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-summary mb-6" data-error-summary tabindex="-1" role="alert">
                <p class="font-bold">{{ __('This role was not updated.') }}</p>
                <ul class="mt-2 grid gap-1">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.roles.update', $managedRole) }}" class="max-w-4xl" data-prevent-duplicate>
            @csrf
            @method('PATCH')

            <div class="admin-panel grid gap-10">
                <x-admin.section :title="__('Role name')" class="border-t-0 pt-0">
                    <div class="max-w-md">
                        <label class="form-label" for="name">{{ __('Name shown when assigning people') }}</label>
                        <input class="form-input" id="name" name="name" value="{{ old('name', $managedRole->name) }}" required>
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                </x-admin.section>

                @foreach ($permissions as $group => $groupPermissions)
                    <x-admin.section :title="__(str($group)->headline()->toString())">
                        <fieldset>
                            <legend class="sr-only">{{ __(str($group)->headline()->toString()) }}</legend>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($groupPermissions as $permission)
                                    <label class="flex gap-3 rounded-lg border border-slate-300 p-3 text-sm">
                                        <input
                                            class="mt-0.5 shrink-0 rounded border-slate-400 text-action-500 focus:ring-knowledge-600"
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission->id }}"
                                            @checked(in_array($permission->id, old('permissions', $managedRole->permissions->pluck('id')->all()), true))
                                        >
                                        <span>
                                            {{-- Business language leads; the technical code is secondary. --}}
                                            <span class="block font-semibold text-brand-950">{{ $permission->description ?: $permission->code }}</span>
                                            <span class="mt-1 block font-mono text-xs text-muted">{{ $permission->code }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </x-admin.section>
                @endforeach
            </div>

            <x-input-error class="mt-4" :messages="$errors->get('permissions')" />

            <div class="mt-6 grid gap-3">
                <p class="text-sm leading-6 text-muted">{{ __('Changing permissions is a high-risk action. It is recorded in the audit history and signs out everyone holding this role.') }}</p>
                <div><button class="button-primary" type="submit">{{ __('Save permissions and revoke affected sessions') }}</button></div>
            </div>
        </form>
    </div>
</x-app-layout>
