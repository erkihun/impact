@props(['disabled' => false])

<input type="search" @disabled($disabled) {{ $attributes->class(['form-input']) }}>
