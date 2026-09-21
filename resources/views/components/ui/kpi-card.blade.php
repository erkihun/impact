@props([
    'value',
    'label',
    'context' => null,
    'href' => null,
    'icon' => null,
])

@if (filled($href))
    <a href="{{ $href }}" {{ $attributes->class(['public-kpi-card public-kpi-card-link']) }}>
        <span class="public-kpi-card-topline" aria-hidden="true"></span>
        <span class="public-kpi-card-header">
            <span class="public-kpi-value">{{ $value }}</span>
            @if (filled($icon))
                <span class="public-kpi-icon" aria-hidden="true">
                    <x-ui.icon :name="$icon" />
                </span>
            @endif
        </span>
        <span class="public-kpi-label">{{ $label }}</span>
        @if (filled($context))
            <span class="public-kpi-context">{{ $context }}</span>
        @endif
        <span class="public-kpi-action" aria-hidden="true">
            <x-ui.icon name="arrow-up-right" />
        </span>
    </a>
@else
    <article {{ $attributes->class(['public-kpi-card']) }}>
        <span class="public-kpi-card-topline" aria-hidden="true"></span>
        <span class="public-kpi-card-header">
            <span class="public-kpi-value">{{ $value }}</span>
            @if (filled($icon))
                <span class="public-kpi-icon" aria-hidden="true">
                    <x-ui.icon :name="$icon" />
                </span>
            @endif
        </span>
        <span class="public-kpi-label">{{ $label }}</span>
        @if (filled($context))
            <span class="public-kpi-context">{{ $context }}</span>
        @endif
    </article>
@endif
