@props([
    'eyebrow',
    'title',
    'summary',
    'updated' => null,
    'sections' => [],
    'composition' => null,
])

@php
    $locale = app()->getLocale();
    $updatedLabel = $updated
        ? __('Last updated').': '.\Illuminate\Support\Carbon::parse($updated)->isoFormat('LL')
        : null;
@endphp

@if ($composition)
    <x-ui.page-composition :composition="$composition" />
@else
<x-ui.page-header
    :eyebrow="$eyebrow"
    :title="$title"
    :description="$summary"
    :meta="$updatedLabel"
    variant="paper"
/>
@endif

<div class="public-section impact-editorial-surface">
<div class="legal-layout content-container">
    {{-- In-page navigation for a long detail page, per section 4.5. --}}
    @if (count($sections) > 1)
        <nav class="legal-toc" aria-labelledby="legal-contents-heading">
            <h2 id="legal-contents-heading" class="eyebrow">{{ __('On this page') }}</h2>
            <ul class="mt-5 grid gap-1 border-s border-slate-300 ps-4">
                @foreach ($sections as $section)
                    <li>
                        <a class="flex min-h-11 items-center text-sm font-semibold text-action-700 underline-offset-4 hover:underline" href="#{{ $section['id'] }}">
                            {{ $section['heading'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    <article class="public-content-canvas reading-width">
        @foreach ($sections as $section)
            <section id="{{ $section['id'] }}" class="scroll-mt-28 border-t border-slate-300 pt-8 first:border-t-0 first:pt-0 [&+section]:mt-12">
                <h2 class="heading-3">{{ $section['heading'] }}</h2>
                @foreach ((array) ($section['body'] ?? []) as $paragraph)
                    <p class="mt-4 text-base leading-8 text-slate-700">{{ $paragraph }}</p>
                @endforeach
                @if (! empty($section['list']))
                    <ul class="mt-4 grid gap-3">
                        @foreach ($section['list'] as $item)
                            <li class="flex gap-3 text-base leading-8 text-slate-700">
                                <span class="mt-3 size-1.5 shrink-0 bg-action-500" aria-hidden="true"></span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach

        {{ $slot }}

        <aside class="mt-12 border-t-4 border-t-knowledge-600 bg-quiet p-6">
            <h2 class="heading-3">{{ __('Questions about this page?') }}</h2>
            <p class="mt-3 text-base leading-8 text-slate-700">{{ __('If anything here is unclear, or you want to exercise a right described above, contact us and we will respond.') }}</p>
            <a class="button-primary mt-5" href="{{ route('contact.create', ['locale' => $locale]) }}">{{ __('Contact us') }}</a>
        </aside>
    </article>
</div>
</div>
