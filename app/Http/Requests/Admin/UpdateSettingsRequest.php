<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\SettingCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.manage') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];
        foreach (SettingCatalog::DEFINITIONS as $key => $definition) {
            $rules[SettingCatalog::inputName($key)] = $definition['rule'];
        }

        return $rules;
    }
}
