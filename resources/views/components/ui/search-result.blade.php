@props(['result', 'number'])

<article {{ $attributes->class(['group public-register-row']) }}>
    <x-ui.editorial-number :number="$number" />
    <div>
        <p class="eyebrow">{{ str($result->searchable_type)->replace('_', ' ')->headline() }}</p>
        <h2 class="mt-3 font-editorial text-xl font-bold leading-tight text-brand-950 sm:text-2xl">
            <a class="group-hover:text-action-700" href="{{ $result->url }}">{{ $result->title }}</a>
        </h2>
        @if (filled($result->summary))
            <p class="mt-4 max-w-3xl text-sm leading-7 text-muted">{{ $result->summary }}</p>
        @endif
    </div>
    <span class="text-xl text-action-700 transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
</article>
