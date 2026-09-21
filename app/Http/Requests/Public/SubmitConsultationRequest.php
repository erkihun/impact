<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Enums\SubmissionType;
use App\Support\Settings\EffectiveSettings;
use App\Support\Settings\EngagementSettings;
use App\Support\Settings\MediaSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

final class SubmitConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = SubmissionType::tryFrom((string) $this->input('type'));

        return $type !== null && app(EngagementSettings::class)->enabled($type);
    }

    /** @return array<string, mixed> */
    public function rules(EngagementSettings $engagement, MediaSettings $media, EffectiveSettings $settings): array
    {
        return [
            'type' => ['required', Rule::enum(SubmissionType::class)],
            'contact_name' => ['required', 'string', 'min:2', 'max:160'],
            'organization_name' => ['nullable', 'string', 'max:200'],
            'role' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'service_id' => ['nullable', 'uuid', 'exists:services,id'],
            'industry_id' => ['nullable', 'uuid', 'exists:industries,id'],
            'description' => ['required', 'string', 'min:20', 'max:10000'],
            'timeframe' => ['nullable', 'string', 'max:100'],
            'budget_range' => ['nullable', 'string', 'max:100'],
            'attachments' => [
                'nullable',
                'array',
                'max:'.$engagement->maximumAttachmentCount(),
            ],
            'attachments.*' => [
                File::types($media->allowedExtensions())
                    ->max($engagement->maximumAttachmentKilobytes()),
            ],
            'privacy_acknowledged' => ['accepted'],
            'policy_version' => [
                'required',
                'string',
                'max:32',
                Rule::in([$settings->string('privacy.policy_version')]),
            ],
            'website' => ['nullable', 'max:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $type = match ($this->route()?->getName()) {
            'rfp-requests.store' => SubmissionType::Rfp->value,
            'contact.store' => SubmissionType::Contact->value,
            default => SubmissionType::Consultation->value,
        };

        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'type' => $type,
            'policy_version' => $this->input(
                'policy_version',
                app(EffectiveSettings::class)->string('privacy.policy_version'),
            ),
        ]);
    }
}
