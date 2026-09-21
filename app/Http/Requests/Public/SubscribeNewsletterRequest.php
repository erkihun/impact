<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Support\Settings\EffectiveSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SubscribeNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(EffectiveSettings $settings): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'marketing_consent' => $settings->boolean('privacy.require_marketing_consent')
                ? ['accepted']
                : ['nullable', 'boolean'],
            'policy_version' => [
                'required',
                'string',
                'max:32',
                Rule::in([$settings->string('privacy.policy_version')]),
            ],
        ];
    }
}
