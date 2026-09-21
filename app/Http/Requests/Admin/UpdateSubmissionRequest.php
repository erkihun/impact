<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('engagement.update-status') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(SubmissionStatus::class)],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
