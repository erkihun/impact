@if (count($actions) > 0)
    <div class="mt-7 flex flex-wrap gap-3">
        @foreach ($actions as $action)
            @php
                $href = $action->external_url;
                if ($action->internal_route && \Illuminate\Support\Facades\Route::has($action->internal_route)) {
                    $href = route($action->internal_route, ['locale' => app()->getLocale()]);
                }
            @endphp
            @if ($href)
                <a class="{{ $action->button_variant->value === 'primary' ? 'button-primary' : 'button-secondary' }}" href="{{ $href }}" @if ($action->open_new_context) target="_blank" rel="noopener noreferrer" @endif>
                    {{ $action->label }}
                    @if ($action->accessible_description)<span class="sr-only">{{ $action->accessible_description }}</span>@endif
                </a>
            @endif
        @endforeach
    </div>
@endif
