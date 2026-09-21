<section>
    <header>
        <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Change your password') }}</h2>
        <p class="mt-2 text-sm leading-6 text-muted">{{ __('Use a long, unique password. A password manager is the easiest way to keep one.') }}</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 grid gap-5">
        @csrf
        @method('put')

        <div>
            <label class="form-label" for="update_password_current_password">{{ __('Current password') }}</label>
            <input class="form-input" id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" required @if ($errors->updatePassword->has('current_password')) aria-invalid="true" @endif>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <label class="form-label" for="update_password_password">{{ __('New password') }}</label>
            <input class="form-input" id="update_password_password" name="password" type="password" autocomplete="new-password" required aria-describedby="update_password_help" @if ($errors->updatePassword->has('password')) aria-invalid="true" @endif>
            <p class="form-help" id="update_password_help">{{ __('Longer passwords are stronger than complicated short ones.') }}</p>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <label class="form-label" for="update_password_password_confirmation">{{ __('Confirm new password') }}</label>
            <input class="form-input" id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required @if ($errors->updatePassword->has('password_confirmation')) aria-invalid="true" @endif>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <button class="button-primary" type="submit">{{ __('Update password') }}</button>

            @if (session('status') === 'password-updated')
                <p class="text-sm font-semibold text-state-success" role="status">{{ __('Your password has been changed.') }}</p>
            @endif
        </div>
    </form>
</section>
