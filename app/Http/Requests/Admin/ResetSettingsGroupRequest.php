<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\SettingCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ResetSettingsGroupRequest extends FormRequest
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
        return [
            'settings_version' => ['required', 'string', 'size:64'],
            'change_reason' => SettingCatalog::categoryRequiresReason($this->category())
                ? ['required', 'string', 'min:10', 'max:500']
                : ['nullable', 'string', 'max:500'],
            'confirm_category' => ['required', 'same:category'],
            'category' => ['required', 'in:'.$this->category()],
        ];
    }

    public function category(): string
    {
        return (string) $this->route('category', 'general');
    }
}
