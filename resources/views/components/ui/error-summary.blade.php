@props(['errors', 'title' => null])

@if ($errors->any())
    <div
        {{ $attributes->merge(['class' => 'error-summary']) }}
        role="alert"
        aria-live="assertive"
        tabindex="-1"
        data-error-summary
    >
        <p class="font-bold">{{ $title ?? __('Please correct the following fields before continuing.') }}</p>
        <ul class="mt-3 list-disc space-y-1 ps-5">
            @foreach ($errors->getMessages() as $field => $messages)
                @foreach ($messages as $message)
                    <li>
                        <a class="font-medium underline underline-offset-2" href="#{{ str($field)->replace('.', '-') }}">{{ $message }}</a>
                    </li>
                @endforeach
            @endforeach
        </ul>
    </div>
@endif
