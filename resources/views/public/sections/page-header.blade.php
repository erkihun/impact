<header class="max-w-4xl">
    @if ($section->content['eyebrow'] ?? null)<p class="eyebrow">{{ $section->content['eyebrow'] }}</p>@endif
    <h1 class="heading-1 mt-4">{{ $headingOverride ?? $section->content['heading'] }}</h1>
    @if ($summaryOverride ?? ($section->content['summary'] ?? null))<p class="reading-width mt-6 text-lg leading-8 text-muted">{{ $summaryOverride ?? $section->content['summary'] }}</p>@endif
</header>
