<div>
    @if ($section->content['eyebrow'] ?? null)<p class="eyebrow">{{ $section->content['eyebrow'] }}</p>@endif
    @if ($section->content['heading'] ?? null)<h2 class="heading-2 mt-4">{{ $section->content['heading'] }}</h2>@endif
    @if ($section->content['summary'] ?? null)<p class="reading-width mt-5 text-muted">{{ $section->content['summary'] }}</p>@endif
    <dl class="mt-8 grid gap-px bg-edge sm:grid-cols-2 lg:grid-cols-4">
        @foreach (($section->content['items'] ?? []) as $item)
            <div class="bg-white p-6"><dt class="text-sm font-bold text-muted">{{ $item['label'] ?? '' }}</dt><dd class="mt-2 font-editorial text-3xl font-bold text-brand-950">{{ $item['value'] ?? '' }}</dd>@if ($item['context'] ?? null)<p class="mt-2 text-sm text-muted">{{ $item['context'] }}</p>@endif</div>
        @endforeach
    </dl>
</div>
