@props(['disabled' => false])

<input type="date" @disabled($disabled) {{ $attributes->class(['form-input']) }}>
