<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Enums\ConsentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'decisions' => ['required', 'array', 'min:1'],
            'decisions.*' => ['required', 'boolean'],
            'policy_version' => [
                'required',
                'string',
                'max:32',
                Rule::in([(string) config('impact.privacy.policy_version')]),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $decisions = collect($this->input('decisions', []))
            ->filter(static fn (mixed $decision, mixed $category): bool => ConsentCategory::tryFrom((string) $category) !== null)
            ->all();

        $this->merge(['decisions' => $decisions]);
    }
}
