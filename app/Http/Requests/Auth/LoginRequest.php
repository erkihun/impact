<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\UserStatus;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();
        $user = User::query()
            ->where('email', Str::lower($this->string('email')->toString()))
            ->first();
        $unavailable = $user !== null && (
            $user->status !== UserStatus::Active
            || $user->locked_until?->isFuture() === true
            || $user->expires_at?->isPast() === true
        );

        if ($unavailable || ! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $settings = app(EffectiveSettings::class);
            $decaySeconds = $settings->integer('authentication.login_throttle_minutes') * 60;
            RateLimiter::hit($this->throttleKey(), $decaySeconds);
            if ($user !== null && ! $unavailable) {
                $attempts = min(255, $user->failed_login_attempts + 1);
                $maximumAttempts = $settings->integer('authentication.max_login_attempts');
                $user->forceFill([
                    'failed_login_attempts' => $attempts,
                    'locked_until' => $attempts >= $maximumAttempts
                        ? now('UTC')->addMinutes($settings->integer('authentication.login_throttle_minutes'))
                        : null,
                ])->save();
                if ($attempts === $maximumAttempts) {
                    SecurityEvent::query()->create([
                        'actor_id' => $user->id,
                        'event' => 'authentication.account_temporarily_locked',
                        'outcome' => 'blocked',
                        'metadata' => [
                            'duration_minutes' => $settings->integer('authentication.login_throttle_minutes'),
                        ],
                        'correlation_id' => (string) $this->attributes->get('correlation_id'),
                    ]);
                }
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $maximumAttempts = app(EffectiveSettings::class)
            ->integer('authentication.max_login_attempts');

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maximumAttempts)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')->toString()).'|'.$this->ip());
    }
}
