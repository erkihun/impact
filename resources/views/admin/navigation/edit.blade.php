<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Global public content')"
            :title="__('Navigation and footer')"
            :description="__('Manage localized link labels, visibility and order without changing route contracts.')"
        />
    </x-slot>

    <div class="admin-workspace">
        <x-admin.error-summary :errors="$errors" />
        <form method="POST" action="{{ route('admin.navigation.update') }}" class="grid gap-6">
            @csrf @method('PUT')
            @php
                $itemIndex = 0;
            @endphp
            @foreach ($items as $group => $groupItems)
                @php
                    [$locale, $location] = explode(':', $group, 2);
                @endphp
                <x-admin.section :title="str($location)->headline()->toString()" :description="strtoupper($locale)">
                    <div class="grid gap-4">
                        @foreach ($groupItems as $item)
                            <fieldset class="grid gap-4 border border-edge bg-paper p-4 lg:grid-cols-[minmax(0,1fr)_8rem_7rem]">
                                <input type="hidden" name="items[{{ $itemIndex }}][id]" value="{{ $item->id }}">
                                <input type="hidden" name="items[{{ $itemIndex }}][lock_version]" value="{{ $item->lock_version }}">
                                <div>
                                    <label class="form-label" for="nav-label-{{ $item->id }}">{{ __('Label') }}</label>
                                    <input class="form-input" id="nav-label-{{ $item->id }}" name="items[{{ $itemIndex }}][label]" value="{{ $item->label }}" required>
                                    <p class="form-help">{{ $item->route_name }} · v{{ $item->version_no }}</p>
                                </div>
                                <div>
                                    <label class="form-label" for="nav-order-{{ $item->id }}">{{ __('Order') }}</label>
                                    <input class="form-input" id="nav-order-{{ $item->id }}" name="items[{{ $itemIndex }}][sort_order]" type="number" min="0" max="1000" value="{{ $item->sort_order }}" required>
                                </div>
                                <label class="flex min-h-11 items-center gap-2 self-end"><input type="hidden" name="items[{{ $itemIndex }}][enabled]" value="0"><input type="checkbox" name="items[{{ $itemIndex }}][enabled]" value="1" @checked($item->enabled)> {{ __('Visible') }}</label>
                            </fieldset>
                            @php($itemIndex++)
                        @endforeach
                    </div>
                </x-admin.section>
            @endforeach
            <div class="sticky bottom-4 flex justify-end"><button class="button-primary shadow-lg" type="submit">{{ __('Publish navigation updates') }}</button></div>
        </form>
    </div>
</x-app-layout>
