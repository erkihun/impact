@php
    $selectedStatus = old('status', $expert->status ?: 'draft');
    $selectedLocale = old('locale', $version->locale ?: app()->getLocale());
    $selectedUserId = old('user_id', $expert->user_id);
    $selectedMediaId = old('profile_media_id', $expert->profile_media_id);
    $selectedMedia = $profileMedia->firstWhere('id', $selectedMediaId) ?? $expert->profileMedia;
    $authorizedAt = old(
        'publication_authorized_at',
        $expert->publication_authorized_at?->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i')
    );
    $qualificationLines = old('qualification_lines', implode(PHP_EOL, $version->qualifications ?? []));
    $languageLines = old('language_lines', implode(', ', $version->languages ?? []));
@endphp

<x-ui.error-summary class="mb-6" :errors="$errors" />

<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
    <div class="grid gap-7">
        <section aria-labelledby="expert-profile-title">
            <div class="editorial-rule-heading">
                <div>
                    <p class="eyebrow">{{ __('Expert profile') }}</p>
                    <h2 id="expert-profile-title" class="mt-2 text-xl font-bold text-brand-950">{{ __('Public profile fields') }}</h2>
                </div>
                <x-ui.status-badge :status="$selectedStatus" />
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="expert-display-name">{{ __('Display name') }}</label>
                    <input class="form-input" id="expert-display-name" name="display_name" required maxlength="180" value="{{ old('display_name', $version->display_name) }}">
                    <x-input-error class="mt-2" :messages="$errors->get('display_name')" />
                </div>
                <div>
                    <label class="form-label" for="expert-title">{{ __('Professional title') }}</label>
                    <input class="form-input" id="expert-title" name="professional_title" required maxlength="220" value="{{ old('professional_title', $version->professional_title) }}">
                    <x-input-error class="mt-2" :messages="$errors->get('professional_title')" />
                </div>
                <div>
                    <label class="form-label" for="expert-slug">{{ __('Slug') }}</label>
                    <input class="form-input" id="expert-slug" name="slug" required maxlength="200" value="{{ old('slug', $version->slug) }}">
                    <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                </div>
                <div>
                    <label class="form-label" for="expert-locale">{{ __('Locale') }}</label>
                    @if ($version->exists)
                        <input type="hidden" name="locale" value="{{ $selectedLocale }}">
                        <input class="form-input bg-quiet" id="expert-locale" value="{{ strtoupper($selectedLocale) }}" disabled>
                    @else
                        <select class="form-input" id="expert-locale" name="locale">
                            @foreach ($locales as $locale)
                                <option value="{{ $locale }}" @selected($selectedLocale === $locale)>{{ strtoupper($locale) }}</option>
                            @endforeach
                        </select>
                    @endif
                    <x-input-error class="mt-2" :messages="$errors->get('locale')" />
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="expert-biography">{{ __('Biography') }}</label>
                    <textarea class="form-input min-h-52" id="expert-biography" name="biography" required maxlength="12000">{{ old('biography', $version->biography) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('biography')" />
                </div>
            </div>
        </section>

        <section class="border-t border-edge pt-7" aria-labelledby="expert-credentials-title">
            <h2 id="expert-credentials-title" class="text-xl font-bold text-brand-950">{{ __('Credentials') }}</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="expert-qualifications">{{ __('Qualifications') }}</label>
                    <textarea class="form-input" id="expert-qualifications" name="qualification_lines" rows="5" maxlength="4000">{{ $qualificationLines }}</textarea>
                    <p class="form-help">{{ __('One qualification per line.') }}</p>
                    <x-input-error class="mt-2" :messages="$errors->get('qualification_lines')" />
                </div>
                <div>
                    <label class="form-label" for="expert-languages">{{ __('Languages') }}</label>
                    <textarea class="form-input" id="expert-languages" name="language_lines" rows="5" maxlength="2000">{{ $languageLines }}</textarea>
                    <p class="form-help">{{ __('Separate languages with commas or new lines.') }}</p>
                    <x-input-error class="mt-2" :messages="$errors->get('language_lines')" />
                </div>
            </div>
        </section>
    </div>

    <aside class="grid gap-5">
        <section class="rounded-lg border border-edge bg-quiet p-4">
            <h2 class="font-bold text-brand-950">{{ __('Profile photo') }}</h2>
            @if ($selectedMedia?->isPubliclyUsable())
                <img
                    class="mt-4 aspect-[4/3] w-full rounded-lg object-cover"
                    src="{{ $selectedMedia->publicUrl() }}"
                    alt="{{ $selectedMedia->alt_text ?: __('Selected expert profile photo') }}"
                >
            @else
                <div class="mt-4 flex aspect-[4/3] w-full items-center justify-center rounded-lg bg-brand-950 text-3xl font-black uppercase text-white" aria-hidden="true">
                    {{ str(old('display_name', $version->display_name ?: '?'))->substr(0, 2)->upper() }}
                </div>
            @endif
            <div class="mt-4">
                <label class="form-label" for="expert-profile-media">{{ __('Approved media') }}</label>
                <select class="form-input" id="expert-profile-media" name="profile_media_id">
                    <option value="">{{ __('No photo') }}</option>
                    @foreach ($profileMedia as $asset)
                        <option value="{{ $asset->id }}" @selected($selectedMediaId === $asset->id)>{{ $asset->title ?: $asset->original_name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('profile_media_id')" />
            </div>
            <div class="mt-4 border-t border-edge pt-4">
                <label class="form-label" for="expert-profile-photo">{{ __('Browse photo') }}</label>
                <input
                    class="form-input file:me-4 file:rounded-md file:border-0 file:bg-action-50 file:px-4 file:py-2 file:text-sm file:font-bold file:text-action-800"
                    id="expert-profile-photo"
                    name="profile_photo"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                >
                <p class="form-help">{{ __('A newly browsed photo is assigned to this expert after upload and replaces the selected media choice.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
            </div>
        </section>

        <section class="rounded-lg border border-edge bg-white p-4">
            <h2 class="font-bold text-brand-950">{{ __('Publication controls') }}</h2>
            <div class="mt-4 grid gap-4">
                <div>
                    <label class="form-label" for="expert-status">{{ __('Profile state') }}</label>
                    <select class="form-input" id="expert-status" name="status">
                        @foreach ($profileStates as $state)
                            <option value="{{ $state }}" @selected($selectedStatus === $state)>{{ str($state)->headline() }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('status')" />
                </div>
                <div>
                    <label class="form-label" for="expert-authorized-at">{{ __('Publication authorized at') }}</label>
                    <input class="form-input" id="expert-authorized-at" name="publication_authorized_at" type="datetime-local" value="{{ $authorizedAt }}">
                    <x-input-error class="mt-2" :messages="$errors->get('publication_authorized_at')" />
                </div>
                <div>
                    <label class="form-label" for="expert-authorization-reference">{{ __('Authorization reference') }}</label>
                    <input class="form-input" id="expert-authorization-reference" name="authorization_reference" maxlength="255" value="{{ old('authorization_reference', $expert->authorization_reference) }}">
                    <x-input-error class="mt-2" :messages="$errors->get('authorization_reference')" />
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-edge bg-white p-4">
            <h2 class="font-bold text-brand-950">{{ __('Administration') }}</h2>
            <div class="mt-4 grid gap-4">
                <div>
                    <label class="form-label" for="expert-user">{{ __('Linked user') }}</label>
                    <select class="form-input" id="expert-user" name="user_id">
                        <option value="">{{ __('No linked user') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($selectedUserId === $user->id)>{{ $user->name }} - {{ $user->email }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('user_id')" />
                </div>
                <div>
                    <label class="form-label" for="expert-years">{{ __('Years experience') }}</label>
                    <input class="form-input" id="expert-years" name="years_experience" type="number" min="0" max="80" value="{{ old('years_experience', $expert->years_experience) }}">
                    <x-input-error class="mt-2" :messages="$errors->get('years_experience')" />
                </div>
                <label class="flex items-start gap-3 text-sm font-semibold text-brand-950">
                    <input type="hidden" name="public_email_enabled" value="0">
                    <input class="mt-1 size-4 rounded border-edge text-action-700" type="checkbox" name="public_email_enabled" value="1" @checked(old('public_email_enabled', $expert->public_email_enabled))>
                    <span>{{ __('Allow public email display') }}</span>
                </label>
            </div>
        </section>

        <div class="grid gap-3">
            <button class="button-primary justify-center" type="submit">{{ $submitLabel }}</button>
            <a class="button-secondary justify-center" href="{{ route('admin.experts.index') }}">{{ __('Cancel') }}</a>
        </div>
    </aside>
</div>
