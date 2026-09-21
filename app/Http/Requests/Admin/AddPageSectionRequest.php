<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ContentSelectionMode;
use App\Enums\PageSectionType;
use App\Enums\VisibilityRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPageSectionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $content = collect((array) $this->input('content', []))
            ->map(function (mixed $value, string $key): mixed {
                if ($key === 'items' && is_string($value) && trim($value) !== '') {
                    return json_decode($value, true);
                }

                return $value === '' ? null : $value;
            })
            ->filter(static fn (mixed $value): bool => $value !== null)
            ->all();
        $presentation = collect((array) $this->input('presentation', []))
            ->filter(static fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();

        $this->merge(compact('content', 'presentation'));
    }

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('composition')) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PageSectionType::class)],
            'variant' => ['required', 'string', 'max:80'],
            'editor_label' => ['required', 'string', 'max:160'],
            'content' => ['present', 'array'],
            'presentation' => ['present', 'array'],
            'enabled' => ['sometimes', 'boolean'],
            'visibility_rule' => ['required', Rule::enum(VisibilityRule::class)],
            'visible_from' => ['nullable', 'date'],
            'visible_until' => ['nullable', 'date', 'after:visible_from'],
            'selection_mode' => ['nullable', Rule::enum(ContentSelectionMode::class)],
            'maximum_items' => ['nullable', 'integer', 'between:1,24'],
            'media_selection_present' => ['sometimes', 'boolean'],
            'media_asset_ids' => ['sometimes', 'array', 'max:8'],
            'media_asset_ids.*' => ['required', 'uuid', 'distinct', 'exists:media_assets,id'],
            'lock_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
