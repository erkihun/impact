<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateNavigationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('navigation.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'max:100'],
            'items.*.id' => ['required', 'uuid', 'distinct', 'exists:page_navigation_configurations,id'],
            'items.*.label' => ['required', 'string', 'max:120'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.sort_order' => ['required', 'integer', 'between:0,1000'],
            'items.*.enabled' => ['sometimes', 'boolean'],
            'items.*.lock_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
