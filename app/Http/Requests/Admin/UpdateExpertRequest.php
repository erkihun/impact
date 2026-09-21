<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ContentWorkflowState;
use App\Models\Expert;
use App\Support\Settings\EffectiveSettings;
use App\Support\Settings\MediaSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateExpertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('experts.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(EffectiveSettings $settings, MediaSettings $media): array
    {
        $expert = $this->route('expert');
        $expertId = $expert instanceof Expert ? $expert->id : null;
        $versionId = $this->input('version_id');

        return [
            'version_id' => [
                'nullable',
                'uuid',
                Rule::exists('expert_versions', 'id')->where('expert_id', $expertId),
            ],
            'user_id' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'status' => ['required', Rule::in(['draft', 'published', 'unpublished', 'archived'])],
            'public_email_enabled' => ['required', 'boolean'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'profile_media_id' => ['nullable', 'uuid', Rule::exists('media_assets', 'id')],
            'profile_photo' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.max(20480, $media->maximumImageKilobytes()),
            ],
            'publication_authorized_at' => ['nullable', 'date'],
            'authorization_reference' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', Rule::in($settings->array('localization.enabled_locales'))],
            'slug' => [
                'required',
                'string',
                'max:200',
                'alpha_dash:ascii',
                Rule::unique('expert_versions', 'slug')
                    ->where('locale', $this->input('locale'))
                    ->ignore(is_string($versionId) ? $versionId : null),
            ],
            'display_name' => ['required', 'string', 'max:180'],
            'professional_title' => ['required', 'string', 'max:220'],
            'biography' => ['required', 'string', 'max:12000'],
            'qualification_lines' => ['nullable', 'string', 'max:4000'],
            'language_lines' => ['nullable', 'string', 'max:2000'],
            'workflow_state' => ['nullable', Rule::enum(ContentWorkflowState::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && filled($this->input('display_name'))) {
            $this->merge(['slug' => Str::slug((string) $this->input('display_name'))]);
        }

        $this->merge([
            'public_email_enabled' => $this->boolean('public_email_enabled'),
            'workflow_state' => $this->input('status') === 'published'
                ? ContentWorkflowState::Published->value
                : ($this->input('workflow_state') ?: ContentWorkflowState::Draft->value),
        ]);
    }
}
