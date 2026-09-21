<x-guest-layout>
    <p class="eyebrow">{{ __('Invitation-only access') }}</p>
    <h1 class="heading-3 mt-3">{{ __('Accept your invitation') }}</h1>
    <p class="mt-3 text-sm leading-6 text-muted">{{ __('Create the password for :email. This invitation can be used only once.', ['email' => $invitation->user->email]) }}</p>

    <x-input-error class="mt-4" :messages="$errors->get('invitation')" />

    <form class="mt-6 space-y-5" method="POST" action="{{ route('invitation.accept', ['token' => $token]) }}" data-prevent-duplicate>
        @csrf
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="mt-1 block w-full" name="name" required autocomplete="name" :value="old('name', $invitation->user->name)" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" name="password" type="password" required autocomplete="new-password" />
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" name="password_confirmation" type="password" required autocomplete="new-password" />
        </div>
        <x-primary-button>{{ __('Activate account') }}</x-primary-button>
    </form>
</x-guest-layout>
