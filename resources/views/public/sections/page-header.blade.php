<header class="m-page-header">
    @if ($section->content['eyebrow'] ?? null)<p class="eyebrow">{{ $section->content['eyebrow'] }}</p>@endif
    <h1>{{ $headingOverride ?? $section->content['heading'] }}</h1>
    @if ($summaryOverride ?? ($section->content['summary'] ?? null))<p class="m-lead">{{ $summaryOverride ?? $section->content['summary'] }}</p>@endif
</header>
