@props(['count', 'query' => null, 'published' => false])

<div {{ $attributes->class(['editorial-rule-heading']) }}>
    <p class="text-sm font-bold text-brand-950" role="status" aria-live="polite">
        {{ trans_choice(':count result|:count results', $count, ['count' => $count]) }}
    </p>
    @if (filled($query))
        <p class="text-sm text-muted">{{ __('Results for “:query”', ['query' => $query]) }}</p>
    @endif
</div>
