@props(['checked' => false])

<input type="radio" @checked($checked) {{ $attributes->class(['form-radio']) }}>
