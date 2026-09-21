<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">
            {{ $enrolling ? __('Secure your account') : __('Multi-factor verification') }}
        </h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            {{ $enrolling
                ? __('Add this account to an authenticator app, then enter the six-digit code.')
                : __('Enter the current code from your authenticator app or one unused recovery code.') }}
        </p>
    </div>

    @if ($enrolling)
        <div class="status-warning mb-5">
            <p class="font-medium">{{ __('Authenticator secret') }}</p>
            <code class="mt-2 block break-all font-mono">{{ $secret }}</code>
            <details class="mt-3">
                <summary class="cursor-pointer font-medium">{{ __('Provisioning URI') }}</summary>
                <code class="mt-2 block break-all text-xs">{{ $provisioningUri }}</code>
            </details>
        </div>
    @endif

    <form method="POST" action="{{ $enrolling ? route('mfa.enroll') : route('mfa.verify') }}" data-prevent-duplicate>
        @csrf
        <x-input-label for="code" :value="$enrolling ? __('Six-digit code') : __('Verification or recovery code')" />
        <x-text-input
            id="code"
            name="code"
            class="mt-1 block w-full font-mono tracking-widest"
            :inputmode="$enrolling ? 'numeric' : 'text'"
            autocomplete="one-time-code"
            required
            autofocus
        />
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
        <x-primary-button class="mt-5">{{ __('Verify') }}</x-primary-button>
    </form>
</x-guest-layout>
