<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Enums\SubmissionType;

final readonly class EngagementSettings
{
    public function __construct(private EffectiveSettings $settings) {}

    public function enabled(SubmissionType $type): bool
    {
        return $this->settings->boolean(match ($type) {
            SubmissionType::Consultation => 'engagement.consultation_form_enabled',
            SubmissionType::Rfp => 'engagement.rfp_form_enabled',
            SubmissionType::Partnership,
            SubmissionType::Media,
            SubmissionType::Contact => 'engagement.contact_form_enabled',
        });
    }

    public function maximumAttachmentCount(): int
    {
        return min(
            $this->settings->integer('engagement.max_attachment_count'),
            (int) config('impact.files.engagement_attachment_max_files', 5),
        );
    }

    public function maximumAttachmentKilobytes(): int
    {
        return min(
            $this->settings->integer('engagement.max_attachment_size_kb'),
            (int) config('impact.files.engagement_attachment_max_kilobytes', 20480),
        );
    }

    public function referencePrefix(): string
    {
        return $this->settings->string('engagement.reference_prefix');
    }

    public function acknowledgementEnabled(): bool
    {
        return $this->settings->boolean('engagement.acknowledgement_enabled');
    }

    public function duplicateWindowHours(): int
    {
        return $this->settings->integer('engagement.duplicate_submission_window_hours');
    }
}
