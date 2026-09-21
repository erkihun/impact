@props(['status', 'tone' => null])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    // Workflow states map to a tone. The tone drives both the colour and the
    // glyph, so status is never communicated by colour alone (WCAG 1.4.1).
    $resolvedTone = $tone ?? match ($value) {
        'published', 'approved', 'active', 'clean', 'ready', 'completed', 'confirmed' => 'success',
        'rejected', 'failed', 'infected', 'disabled', 'cancelled' => 'danger',
        'changes_requested', 'pending', 'scanning', 'processing', 'quarantined' => 'warning',
        'in_review', 'scheduled', 'received' => 'info',
        'draft', 'archived', 'inactive', 'unpublished' => 'neutral',
        default => 'neutral',
    };

    $classes = 'status-badge-'.$resolvedTone;

    $glyph = match ($resolvedTone) {
        'success' => '✓',
        'danger' => '✕',
        'warning' => '!',
        'info' => '◷',
        default => '•',
    };
@endphp

<span {{ $attributes->merge(['class' => "status-badge {$classes}"]) }}>
    <span class="status-badge-glyph" aria-hidden="true">{{ $glyph }}</span>
    {{ __(str($value)->headline()->toString()) }}
</span>
