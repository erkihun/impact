@props([
    'action',
    'query' => '',
    'id' => 'public-search-query',
    'label' => null,
    'placeholder' => null,
    'compact' => false,
])

<form
    method="GET"
    action="{{ $action }}"
    {{ $attributes->class([
        'impact-paper border-t-4 border-brand-950 p-5 sm:p-7',
        'sm:grid sm:grid-cols-[1fr_auto] sm:items-end sm:gap-4' => $compact,
    ]) }}
    role="search"
>
    <div>
        <label class="form-label" for="{{ $id }}">{{ $label ?: __('Search the website') }}</label>
        <div class="{{ $compact ? '' : 'mt-2 flex flex-col gap-3 sm:flex-row' }}">
            <input
                class="form-input min-w-0 flex-1"
                id="{{ $id }}"
                name="q"
                type="search"
                value="{{ $query }}"
                maxlength="200"
                placeholder="{{ $placeholder ?: __('Search insights, services and more') }}"
                enterkeyhint="search"
            >
            @unless ($compact)
                <button class="button-primary shrink-0 justify-center" type="submit">{{ __('Search') }}</button>
            @endunless
        </div>
    </div>
    @if ($compact)
        <div class="mt-3 flex flex-wrap gap-2 sm:mt-0">
            <button class="button-primary" type="submit">{{ __('Search') }}</button>
            @if ($query !== '')
                <a class="button-secondary" href="{{ $action }}">{{ __('Clear search') }}</a>
            @endif
        </div>
    @endif
</form>
