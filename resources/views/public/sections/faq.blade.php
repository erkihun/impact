<div class="mx-auto max-w-4xl">
    @if ($section->content['eyebrow'] ?? null)<p class="eyebrow">{{ $section->content['eyebrow'] }}</p>@endif
    <h2 class="heading-2 mt-4">{{ $section->content['heading'] }}</h2>
    <div class="mt-8 divide-y divide-edge border-y border-edge">
        @foreach (($section->content['items'] ?? []) as $item)
            <details class="group py-5"><summary class="cursor-pointer font-bold">{{ $item['question'] ?? '' }}</summary><p class="mt-4 leading-7 text-muted">{{ $item['answer'] ?? '' }}</p></details>
        @endforeach
    </div>
</div>
