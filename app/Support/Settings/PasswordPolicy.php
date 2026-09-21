<?php

declare(strict_types=1);

namespace App\Support\Settings;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final readonly class PasswordPolicy
{
    public function __construct(private EffectiveSettings $settings) {}

    /** @return list<mixed> */
    public function rules(): array
    {
        $password = Password::min($this->settings->integer('authentication.password_min_length'))
            ->max($this->settings->integer('authentication.password_max_length'));

        if ($this->settings->boolean('authentication.require_uppercase')
            && $this->settings->boolean('authentication.require_lowercase')) {
            $password->mixedCase();
        } elseif ($this->settings->boolean('authentication.require_uppercase')) {
            $password->rules(['/[\p{Lu}]/u']);
        } elseif ($this->settings->boolean('authentication.require_lowercase')) {
            $password->rules(['/[\p{Ll}]/u']);
        }

        if ($this->settings->boolean('authentication.require_number')) {
            $password->numbers();
        }

        if ($this->settings->boolean('authentication.require_symbol')) {
            $password->symbols();
        }

        $rules = [$password];

        if ($this->settings->boolean('authentication.prevent_common_passwords')) {
            $rules[] = Rule::notIn([
                'password',
                'password123',
                '12345678',
                '123456789',
                'qwerty123',
                'admin123',
                'letmein',
                'welcome',
                'impact123',
            ]);
        }

        return $rules;
    }
}
