<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ContentWorkflowState;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TransitionContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->hasPermission('content.submit')
            || $user->hasPermission('content.approve')
            || $user->hasPermission('content.publish')
            || $user->hasPermission('content.rollback')
        );
    }

    /** @return array<string, mixed> */
    public function rules(EffectiveSettings $settings): array
    {
        $reasonRequired = $settings->boolean('content.require_publication_reason')
            && in_array($this->input('to'), [
                ContentWorkflowState::Published->value,
                ContentWorkflowState::Scheduled->value,
            ], true);

        return [
            'to' => ['required', Rule::enum(ContentWorkflowState::class)],
            'content_version_id' => ['required', 'uuid', 'exists:content_versions,id'],
            'note' => [$reasonRequired ? 'required' : 'nullable', 'string', 'max:2000'],
            'publish_at' => ['nullable', 'required_if:to,scheduled', 'date', 'after:now'],
            'unpublish_at' => ['nullable', 'date', 'after:publish_at'],
        ];
    }
}
