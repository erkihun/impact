<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Editorial operations')"
            :title="__('Create content draft')"
            :description="__('Start a new immutable draft. Workflow and publication remain controlled after the record is created.')"
        />
    </x-slot>

    <div class="admin-workspace">
        <form method="POST" action="{{ route('admin.content.store') }}" class="admin-panel mx-auto max-w-4xl" data-prevent-duplicate>
            @csrf
            <x-ui.error-summary class="mb-6" :errors="$errors" />
            <div class="grid gap-6 sm:grid-cols-2">
                <div><label class="form-label" for="content-type">{{ __('Type') }}</label><select class="form-input" id="content-type" name="type">@foreach ($types as $type)<option value="{{ $type->value }}">{{ str($type->value)->headline() }}</option>@endforeach</select></div>
                <div><label class="form-label" for="content-locale">{{ __('Locale') }}</label><select class="form-input" id="content-locale" name="locale">@foreach ($locales as $locale)<option value="{{ $locale }}">{{ strtoupper($locale) }}</option>@endforeach</select></div>
                <div class="sm:col-span-2"><label class="form-label" for="content-title">{{ __('Title') }}</label><input class="form-input" id="content-title" name="title" required value="{{ old('title') }}"></div>
                <div class="sm:col-span-2"><label class="form-label" for="content-slug">{{ __('Slug') }}</label><input class="form-input" id="content-slug" name="slug" required value="{{ old('slug') }}"></div>
                <div class="sm:col-span-2"><label class="form-label" for="content-summary">{{ __('Summary') }}</label><textarea class="form-input" id="content-summary" name="summary">{{ old('summary') }}</textarea></div>
                <div class="sm:col-span-2"><label class="form-label" for="content-body">{{ __('Body') }}</label><textarea class="form-input min-h-72" id="content-body" name="body" required>{{ old('body') }}</textarea></div>
            </div>
            <div class="admin-button-group mt-8">
                <button class="button-primary" type="submit">{{ __('Save draft') }}</button>
                <a class="button-secondary" href="{{ route('admin.content.index') }}">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
