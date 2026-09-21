<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Media governance')"
            :title="__('Media security queue')"
            :description="__('Every upload is quarantined and scanned before it can be published. Approve an asset only once its scan is clean and processing is complete.')"
        />
    </x-slot>

    <div class="admin-workspace">
        <div class="grid gap-8 lg:grid-cols-[22rem_minmax(0,1fr)] lg:items-start">
            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.media.store') }}" class="admin-panel h-fit lg:sticky lg:top-24">
                @csrf
                <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Quarantine an upload') }}</h2>
                <p class="mt-2 text-sm leading-6 text-muted">{{ __('The file is stored in quarantine and scanned. It is not publicly reachable until it is approved.') }}</p>

                <div class="mt-5 grid gap-4">
                    <div>
                        <label class="form-label" for="media-file">{{ __('File') }}</label>
                        <input class="form-input" id="media-file" name="file" type="file" required aria-describedby="media-file-help">
                        <p class="form-help" id="media-file-help">{{ __('Select the original, highest-quality version. Responsive sizes are generated for you.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('file')" />
                    </div>
                    <div>
                        <label class="form-label" for="media-visibility">{{ __('Visibility') }}</label>
                        <select class="form-input" id="media-visibility" name="visibility">
                            <option value="public">{{ __('Public after approval') }}</option>
                            <option value="private">{{ __('Private') }}</option>
                            <option value="restricted">{{ __('Restricted') }}</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('visibility')" />
                    </div>
                    <div>
                        <label class="form-label" for="media-title">{{ __('Title') }}</label>
                        <input class="form-input" id="media-title" name="title" value="{{ old('title') }}">
                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    </div>
                    <div>
                        <label class="form-label" for="media-alt">{{ __('Alternative text') }}</label>
                        <textarea class="form-input" id="media-alt" name="alt_text" rows="3" aria-describedby="media-alt-help">{{ old('alt_text') }}</textarea>
                        <p class="form-help" id="media-alt-help">{{ __('Describe what the image shows and why it matters. Leave empty only if the image is purely decorative.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('alt_text')" />
                    </div>
                    <button class="button-primary justify-center" type="submit">{{ __('Upload to quarantine') }}</button>
                </div>
            </form>

            <div>
                <x-admin.result-summary :paginator="$assets" :context="__('Media register')" />

                @if ($assets->total() > 0)
                    <div class="mt-5">
                        <x-admin.record-list
                            :label="__('Media assets')"
                            :columns="[
                                ['label' => __('File')],
                                ['label' => __('Scan')],
                                ['label' => __('Processing')],
                                ['label' => __('Alternative text')],
                                ['label' => __('Added')],
                                ['label' => __('Actions'), 'srOnly' => true],
                            ]"
                        >
                            @foreach ($assets as $asset)
                                @php
                                    $primaryVariant = $asset->variants
                                        ->filter(fn ($variant): bool => $variant->width !== null && $variant->height !== null)
                                        ->sortByDesc(fn ($variant): int => (int) $variant->width * (int) $variant->height)
                                        ->first();
                                @endphp
                                <tr>
                                    <x-admin.cell :label="__('File')" primary>
                                        {{ $asset->title ?: $asset->original_name }}
                                        <span class="mt-1 block text-sm font-normal text-muted">
                                            {{ $asset->mime_type }}
                                            @if ($asset->size_bytes)
                                                · {{ round($asset->size_bytes / 1024) >= 1024 ? round($asset->size_bytes / 1048576, 1).' MB' : round($asset->size_bytes / 1024).' KB' }}
                                            @endif
                                            @if ($primaryVariant)
                                                · {{ $primaryVariant->width }}×{{ $primaryVariant->height }}
                                            @endif
                                        </span>
                                    </x-admin.cell>
                                    <x-admin.cell :label="__('Scan')"><x-ui.status-badge :status="$asset->scan_status" /></x-admin.cell>
                                    <x-admin.cell :label="__('Processing')"><x-ui.status-badge :status="$asset->processing_status" /></x-admin.cell>
                                    <x-admin.cell :label="__('Alternative text')">
                                        @if (filled($asset->alt_text))
                                            {{ $asset->alt_text }}
                                        @else
                                            <span class="font-semibold text-state-warning">{{ __('Not yet recorded') }}</span>
                                        @endif
                                    </x-admin.cell>
                                    <x-admin.cell :label="__('Added')">
                                        @if ($asset->created_at)
                                            <time datetime="{{ $asset->created_at->toIso8601String() }}">{{ $asset->created_at->isoFormat('LLL') }}</time>
                                        @endif
                                    </x-admin.cell>
                                    <x-admin.row-actions :label="__('Actions')">
                                        @if ($asset->scan_status === \App\Enums\MediaStatus::Clean && $asset->processing_status === \App\Enums\MediaStatus::Ready && str_starts_with($asset->path, 'quarantine/'))
                                            <form method="POST" action="{{ route('admin.media.approve', $asset) }}" data-prevent-duplicate>
                                                @csrf
                                                <button class="button-tertiary" type="submit">{{ __('Approve for use') }}</button>
                                            </form>
                                        @elseif (! str_starts_with($asset->path, 'quarantine/'))
                                            <a class="button-tertiary" href="{{ URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['media' => $asset]) }}">{{ __('Download') }}</a>
                                        @else
                                            <span class="text-sm text-muted">{{ __('Awaiting security checks') }}</span>
                                        @endif
                                    </x-admin.row-actions>
                                </tr>
                            @endforeach
                        </x-admin.record-list>
                    </div>
                    <div class="mt-6">{{ $assets->links() }}</div>
                @else
                    <div class="mt-5">
                        <x-ui.empty-state
                            :title="__('No media assets yet.')"
                            :description="__('Upload the first file using the form beside this list. It will be scanned before it can be approved for publication.')"
                        />
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
