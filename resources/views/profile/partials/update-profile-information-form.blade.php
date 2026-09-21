<section>
    <header>
        <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Profile information') }}</h2>
        <p class="mt-2 text-sm leading-6 text-muted">{{ __('Your name is shown to colleagues on records you own. Your email address is used to sign in.') }}</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 grid gap-5">
        @csrf
        @method('patch')

        <div>
            <label class="form-label" for="name">{{ __('Name') }}</label>
            <input class="form-input" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" @if ($errors->has('name')) aria-invalid="true" @endif>
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <label class="form-label" for="email">{{ __('Email address') }}</label>
            <input class="form-input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" @if ($errors->has('email')) aria-invalid="true" @endif>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="status-warning mt-3">
                    <p>{{ __('Your email address is not verified.') }}</p>
                    <button form="send-verification" class="mt-2 inline-flex min-h-11 items-center font-bold text-action-700 underline underline-offset-4 hover:text-action-900">
                        {{ __('Send the verification email again') }}
                    </button>
                </div>

                @if (session('status') === 'verification-link-sent')
                    <p class="status-success mt-3" role="status">{{ __('A new verification link has been sent to your email address.') }}</p>
                @endif
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <button class="button-primary" type="submit">{{ __('Save changes') }}</button>

            @if (session('status') === 'profile-updated')
                <p class="text-sm font-semibold text-state-success" role="status">{{ __('Your profile has been updated.') }}</p>
            @endif
        </div>
    </form>
</section>
