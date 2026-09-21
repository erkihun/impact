@props([
    'items' => [],
    'dark' => false,
    'label' => null,
])

<section {{ $attributes->class(['impact-index', 'impact-index-dark' => $dark]) }} @if($label) aria-label="{{ $label }}" @endif>
    @foreach ($items as $item)
        <x-ui.kpi-card
            :value="data_get($item, 'value')"
            :label="data_get($item, 'label')"
            :context="data_get($item, 'context')"
            :href="data_get($item, 'href')"
            :icon="data_get($item, 'icon')"
        />
    @endforeach
</section>
