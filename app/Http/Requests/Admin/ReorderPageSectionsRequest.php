<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderPageSectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('composition')) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'section_ids' => ['required', 'array'],
            'section_ids.*' => ['required', 'uuid', 'distinct'],
            'lock_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
