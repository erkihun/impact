<header class="m-page-header">
    @if ($section->content['eyebrow'] ?? null)<p class="eyebrow">{{ $section->content['eyebrow'] }}</p>@endif
    <h1>{{ $section->content['heading'] }}</h1>
    <p class="m-lead">{{ $section->content['summary'] }}</p>
    @if ($section->content['privacy_guidance'] ?? null)<p class="m-meta mx-auto max-w-xl">{{ $section->content['privacy_guidance'] }}</p>@endif
</header>
