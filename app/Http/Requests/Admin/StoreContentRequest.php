<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ContentType;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('content.create') === true;
    }

    /** @return array<string, mixed> */
    public function rules(EffectiveSettings $settings): array
    {
        return [
            'type' => ['required', Rule::enum(ContentType::class)],
            'locale' => ['required', Rule::in($settings->array('localization.enabled_locales'))],
            'slug' => [
                $settings->boolean('content.auto_generate_slugs') ? 'nullable' : 'required',
                'string',
                'max:200',
                'alpha_dash:ascii',
                Rule::unique('content_slugs')->where('locale', $this->input('locale')),
            ],
            'title' => ['required', 'string', 'max:220'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'body' => ['required', 'string', 'max:100000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (app(EffectiveSettings::class)->boolean('content.auto_generate_slugs')
            && blank($this->input('slug'))
            && filled($this->input('title'))) {
            $this->merge(['slug' => Str::slug((string) $this->input('title'))]);
        }
    }
}
