<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\SettingCatalog;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateSettingsGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.manage') === true
            && SettingCatalog::categoryExists($this->category());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'settings_version' => ['required', 'string', 'size:64'],
            'change_reason' => SettingCatalog::categoryRequiresReason($this->category())
                ? ['required', 'string', 'min:10', 'max:500']
                : ['nullable', 'string', 'max:500'],
        ];

        $settings = app(EffectiveSettings::class);
        foreach (SettingCatalog::forCategory($this->category()) as $key => $definition) {
            if (! $settings->resolve($key)->isEditable) {
                continue;
            }

            $rules[SettingCatalog::inputName($key)] = $definition['rule'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $allowed = collect(array_keys($this->rules()))
                ->merge(['_token', '_method'])
                ->all();

            $unknown = collect($this->request->keys())
                ->reject(fn (string $key): bool => in_array($key, $allowed, true))
                ->values();

            if ($unknown->isNotEmpty()) {
                $validator->errors()->add('settings', __('Unknown settings cannot be saved.'));
            }
        });
    }

    public function category(): string
    {
        return (string) $this->route('category', 'general');
    }
}
