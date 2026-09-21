@props(['value' => 0, 'max' => 100, 'label' => null])

@if ($label)
    <label class="form-label">{{ $label }}</label>
@endif
<progress class="h-2 w-full accent-action-500" value="{{ $value }}" max="{{ $max }}">{{ $value }}%</progress>
