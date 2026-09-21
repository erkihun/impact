<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('People directory')"
            :title="__('Experts')"
            :description="__('Manage approved expert profiles, credentials, profile photos and public publication state from a dedicated register.')"
        >
            <x-slot name="action">
                <a class="button-primary" href="{{ route('admin.experts.create') }}">{{ __('Create expert') }} <span aria-hidden="true">+</span></a>
            </x-slot>
        </x-admin.page-header>
    </x-slot>

    <div class="admin-workspace">
        <x-admin.result-summary :paginator="$experts" :context="__('Expert profile register')" />

        @if ($experts->total() > 0)
            <div class="mt-5">
                <x-admin.record-list
                    :label="__('Experts')"
                    :columns="[
                        ['label' => __('Expert')],
                        ['label' => __('Profile state')],
                        ['label' => __('Public version')],
                        ['label' => __('Experience')],
                        ['label' => __('Linked user')],
                        ['label' => __('Updated')],
                        ['label' => __('Actions'), 'srOnly' => true],
                    ]"
                >
                    @foreach ($experts as $expert)
                        @php
                            $version = $expert->versions->firstWhere('locale', app()->getLocale()) ?? $expert->versions->first();
                            $media = $expert->profileMedia;
                        @endphp
                        <tr>
                            <x-admin.cell :label="__('Expert')" primary>
                                <span class="flex items-center gap-3">
                                    @if ($media?->isPubliclyUsable())
                                        <img
                                            class="size-12 rounded-lg object-cover"
                                            src="{{ $media->publicUrl() }}"
                                            alt="{{ $media->alt_text ?: ($version?->display_name ?? __('Expert photo')) }}"
                                        >
                                    @else
                                        <span class="flex size-12 items-center justify-center rounded-lg bg-brand-950 text-sm font-black uppercase text-white" aria-hidden="true">
                                            {{ str($version?->display_name ?? '?')->substr(0, 2)->upper() }}
                                        </span>
                                    @endif
                                    <span class="min-w-0">
                                        {{ $version?->display_name ?? __('Unnamed expert') }}
                                        <span class="mt-1 block text-sm font-normal text-muted">{{ $version?->professional_title ?? __('No title recorded') }}</span>
                                    </span>
                                </span>
                            </x-admin.cell>
                            <x-admin.cell :label="__('Profile state')"><x-ui.status-badge :status="$expert->status" /></x-admin.cell>
                            <x-admin.cell :label="__('Public version')">
                                @if ($version)
                                    <span class="font-semibold text-brand-950">{{ strtoupper($version->locale) }}</span>
                                    <span class="mt-1 block text-sm text-muted">{{ str((string) $version->getRawOriginal('workflow_state'))->headline() }}</span>
                                @else
                                    <span class="font-semibold text-state-warning">{{ __('Missing') }}</span>
                                @endif
                            </x-admin.cell>
                            <x-admin.cell :label="__('Experience')">
                                @if ($expert->years_experience !== null)
                                    {{ __(':years years', ['years' => $expert->years_experience]) }}
                                @else
                                    <span class="text-muted">{{ __('Not recorded') }}</span>
                                @endif
                            </x-admin.cell>
                            <x-admin.cell :label="__('Linked user')">
                                @if ($expert->user)
                                    {{ $expert->user->name }}
                                    <span class="mt-1 block text-sm text-muted">{{ $expert->user->email }}</span>
                                @else
                                    <span class="text-muted">{{ __('None') }}</span>
                                @endif
                            </x-admin.cell>
                            <x-admin.cell :label="__('Updated')">
                                @if ($expert->updated_at)
                                    <time datetime="{{ $expert->updated_at->toIso8601String() }}">{{ $expert->updated_at->isoFormat('LLL') }}</time>
                                @endif
                            </x-admin.cell>
                            <x-admin.row-actions :label="__('Actions')">
                                <a class="button-tertiary" href="{{ route('admin.experts.edit', $expert) }}">{{ __('Edit') }}</a>
                            </x-admin.row-actions>
                        </tr>
                    @endforeach
                </x-admin.record-list>
            </div>
            <div class="mt-6">{{ $experts->links() }}</div>
        @else
            <div class="mt-5">
                <x-ui.empty-state
                    :title="__('No experts have been registered yet.')"
                    :description="__('Create the first expert profile with its biography, credentials, languages and publication controls.')"
                >
                    <a class="button-primary" href="{{ route('admin.experts.create') }}">{{ __('Create expert') }}</a>
                </x-ui.empty-state>
            </div>
        @endif
    </div>
</x-app-layout>
