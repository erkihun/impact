<div class="grid items-center gap-10 lg:grid-cols-2">
    @include('public.sections.partials.media', ['media' => $section->media])
    <div>
        @if ($section->content['eyebrow'] ?? null)<p class="eyebrow">{{ $section->content['eyebrow'] }}</p>@endif
        <h2 class="heading-2 mt-4">{{ $section->content['heading'] }}</h2>
        @if ($section->content['summary'] ?? null)<p class="mt-5 leading-8 text-muted">{{ $section->content['summary'] }}</p>@endif
        @include('public.sections.partials.actions', ['actions' => $section->actions])
    </div>
</div>
