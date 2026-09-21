<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Your account')"
            :title="__('Profile and security')"
            :description="__('Update how you appear to colleagues, change your password and manage access to this account.')"
        />
    </x-slot>

    <div class="admin-workspace">
        <div class="grid max-w-3xl gap-6">
            <section class="admin-panel">
                @include('profile.partials.update-profile-information-form')
            </section>

            <section class="admin-panel">
                @include('profile.partials.update-password-form')
            </section>

            <section class="admin-panel border-s-4 border-s-danger">
                @include('profile.partials.delete-user-form')
            </section>
        </div>
    </div>
</x-app-layout>
