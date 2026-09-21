<div class="reading-width">
    @if ($section->content['eyebrow'] ?? null)<p class="eyebrow">{{ $section->content['eyebrow'] }}</p>@endif
    @if ($section->content['heading'] ?? null)<h2 class="heading-2 mt-4">{{ $section->content['heading'] }}</h2>@endif
    <div class="mt-6 whitespace-pre-line text-base leading-8">{{ $section->content['body'] }}</div>
</div>
