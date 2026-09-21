@props(['items' => []])

<nav class="breadcrumbs" aria-label="{{ __('Breadcrumb') }}">
    <ol class="flex flex-wrap items-center gap-2">
        @foreach ($items as $label => $url)
            <li class="flex items-center gap-2">
                @if (! $loop->last && filled($url))
                    <a href="{{ $url }}">{{ $label }}</a>
                    <span aria-hidden="true">/</span>
                @else
                    <span aria-current="page" class="font-medium text-brand-900">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
