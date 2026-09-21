<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PageCompositionState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TransitionPageCompositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pages.update') === true
            || $this->user()?->hasPermission('pages.approve') === true
            || $this->user()?->hasPermission('pages.publish') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'to' => ['required', Rule::enum(PageCompositionState::class)],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
