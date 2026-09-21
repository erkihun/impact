<div>
    @if ($section->content['eyebrow'] ?? null)<p class="eyebrow">{{ $section->content['eyebrow'] }}</p>@endif
    <h2 class="heading-2 mt-4">{{ $section->content['heading'] }}</h2>
    @if ($section->content['summary'] ?? null)<p class="reading-width mt-5 text-muted">{{ $section->content['summary'] }}</p>@endif
    @if (count($section->relations) > 0)
        <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($section->relations as $relation)
                <article class="border border-edge bg-white p-6">
                    <p class="eyebrow">{{ str($relation->relation_type)->headline() }}</p>
                    <h3 class="mt-3 font-editorial text-xl font-bold">{{ $relation->related?->currentVersion?->title ?? __('Published item') }}</h3>
                </article>
            @endforeach
        </div>
    @else
        <x-ui.empty-state :title="$section->content['empty_title'] ?? __('Nothing published yet.')" :description="$section->content['empty_summary'] ?? ''" />
    @endif
</div>
