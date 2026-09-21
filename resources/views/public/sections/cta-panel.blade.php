<div class="grid items-end gap-7 lg:grid-cols-[1fr_auto]">
    <div>@if ($section->content['eyebrow'] ?? null)<p class="eyebrow">{{ $section->content['eyebrow'] }}</p>@endif<h2 class="heading-2 mt-4">{{ $section->content['heading'] }}</h2>@if ($section->content['summary'] ?? null)<p class="reading-width mt-4 leading-7 opacity-80">{{ $section->content['summary'] }}</p>@endif</div>
    @include('public.sections.partials.actions', ['actions' => $section->actions])
</div>
