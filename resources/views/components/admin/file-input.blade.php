@props(['disabled' => false])

<input type="file" @disabled($disabled) {{ $attributes->class(['form-input']) }}>
