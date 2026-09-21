@props(['checked' => false])

<input type="checkbox" @checked($checked) {{ $attributes->class(['form-checkbox']) }}>
