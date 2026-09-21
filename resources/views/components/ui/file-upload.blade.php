@props([
    'id',
    'name',
    'label',
    'accept' => null,
    'required' => false,
    'help' => null,
    'multiple' => false,
])

@php($validationKey = (string) str($name)->before('['))

<div {{ $attributes }}>
    <label class="form-label" for="{{ $id }}">
        {{ $label }}
        @if ($required)<span class="form-required">({{ __('required') }})</span>@endif
    </label>
    <div class="file-upload-control">
        <svg class="size-6 shrink-0 text-action-700" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M12 16V4m0 0 4 4m-4-4L8 8M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/>
        </svg>
        <input
            class="min-w-0 max-w-full flex-1 overflow-hidden text-sm file:me-4 file:min-h-11 file:rounded-lg file:border-0 file:bg-brand-950 file:px-4 file:font-bold file:text-white hover:file:bg-action-700"
            id="{{ $id }}"
            name="{{ $name }}"
            type="file"
            @if ($accept) accept="{{ $accept }}" @endif
            @required($required)
            @if ($multiple) multiple @endif
            @error($validationKey) aria-invalid="true" @enderror
        >
    </div>
    @if ($help)<p class="form-help mt-2">{{ $help }}</p>@endif
    @error($validationKey)<p class="field-error">{{ $message }}</p>@enderror
    @if ($multiple)
        @error($validationKey.'.*')<p class="field-error">{{ $message }}</p>@enderror
    @endif
</div>
