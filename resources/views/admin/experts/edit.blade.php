<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('People directory')"
            :title="old('display_name', $version->display_name ?: __('Edit expert'))"
            :description="__('Update profile details, credentials, photo assignment and publication controls for this expert.')"
        >
            <x-slot name="action">
                <a class="button-secondary" href="{{ route('admin.experts.index') }}">{{ __('Back to experts') }}</a>
            </x-slot>
        </x-admin.page-header>
    </x-slot>

    <div class="admin-workspace">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
            <form method="POST" action="{{ route('admin.experts.update', $expert) }}" enctype="multipart/form-data" class="admin-panel" data-prevent-duplicate>
                @csrf
                @method('PATCH')
                @if ($version->exists)
                    <input type="hidden" name="version_id" value="{{ $version->id }}">
                @endif
                @include('admin.experts._form', ['submitLabel' => __('Save expert')])
            </form>

            <aside class="admin-panel h-fit lg:sticky lg:top-24">
                <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Delete expert') }}</h2>
                <p class="mt-3 text-sm leading-7 text-muted">{{ __('Deleting removes the expert profile and its localized public profile version from the dedicated expert register.') }}</p>
                <form method="POST" action="{{ route('admin.experts.destroy', $expert) }}" class="mt-6" data-confirm="{{ __('Delete this expert profile?') }}">
                    @csrf
                    @method('DELETE')
                    <button class="button-danger w-full justify-center" type="submit">{{ __('Delete expert') }}</button>
                </form>
            </aside>
        </div>
    </div>
</x-app-layout>
