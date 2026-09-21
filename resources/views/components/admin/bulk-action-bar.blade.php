@props(['count' => 0])

<div {{ $attributes->class(['admin-bulk-action-bar']) }} role="region" aria-label="{{ __('Bulk actions') }}">
    <p class="text-sm font-bold text-brand-950">
        {{ trans_choice(':count selected record|:count selected records', $count, ['count' => $count]) }}
    </p>
    <div class="admin-button-group">{{ $slot }}</div>
</div>
