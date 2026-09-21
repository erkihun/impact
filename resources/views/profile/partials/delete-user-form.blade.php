<section>
    <header>
        <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Delete this account') }}</h2>
        <p class="mt-2 text-sm leading-6 text-muted">
            {{ __('Deleting your account removes your access immediately and cannot be undone. Audit records of actions you already took are retained, because that history cannot be altered.') }}
        </p>
    </header>

    <button class="button-danger mt-5" type="button" data-open-modal="confirm-user-deletion">
        {{ __('Delete this account') }}
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" labelledby="confirm-user-deletion-title">
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            {{-- Destructive confirmation names the item, the action and the consequence. --}}
            <h2 id="confirm-user-deletion-title" class="font-editorial text-lg font-bold text-brand-950">
                {{ __('Delete the account for :email?', ['email' => auth()->user()?->email]) }}
            </h2>

            <p class="mt-2 text-sm leading-6 text-muted">
                {{ __('You will be signed out immediately and will lose access to every workspace assigned to you. This cannot be undone. Enter your password to confirm.') }}
            </p>

            <div class="mt-6">
                <label class="form-label" for="delete-account-password">{{ __('Your current password') }}</label>
                <input
                    class="form-input"
                    id="delete-account-password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    @if ($errors->userDeletion->isNotEmpty()) aria-invalid="true" @endif
                >
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button type="button" class="button-secondary" data-close-modal>{{ __('Keep my account') }}</button>
                <button type="submit" class="button-danger">{{ __('Delete this account permanently') }}</button>
            </div>
        </form>
    </x-modal>
</section>
