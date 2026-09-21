<x-guest-layout>
    <h1 class="auth-title">{{ __('Verify your email address') }}</h1>
    <div class="auth-intro mt-3">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm font-semibold text-state-success">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}" data-prevent-duplicate>
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="rounded-md text-sm text-muted underline hover:text-ink focus:outline-none focus:ring-2 focus:ring-knowledge-600 focus:ring-offset-2">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
