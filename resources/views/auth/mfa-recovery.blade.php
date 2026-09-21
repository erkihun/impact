<x-guest-layout>
    <h1 class="text-2xl font-semibold text-slate-900">{{ __('Save your recovery codes') }}</h1>
    <p class="mt-2 text-sm leading-6 text-slate-600">
        {{ __('Store these one-time codes in a password manager. They will not be shown again.') }}
    </p>
    <ul class="mt-5 grid grid-cols-2 gap-2 rounded-lg bg-slate-950 p-5 font-mono text-sm text-white">
        @foreach ($codes as $code)
            <li>{{ $code }}</li>
        @endforeach
    </ul>
    <a href="{{ route('admin.dashboard') }}" class="button-primary mt-6">
        {{ __('Continue to administration') }}
    </a>
</x-guest-layout>
