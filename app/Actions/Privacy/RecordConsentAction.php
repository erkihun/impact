<?php

declare(strict_types=1);

namespace App\Actions\Privacy;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Privacy\RecordConsentData;
use App\Enums\ConsentCategory;
use App\Models\ConsentRecord;
use Illuminate\Support\Facades\DB;

final readonly class RecordConsentAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(RecordConsentData $data): void
    {
        DB::transaction(function () use ($data): void {
            $recordIds = [];

            foreach ($data->decisions as $category => $decision) {
                $consentCategory = ConsentCategory::tryFrom($category);

                if ($consentCategory === null || $consentCategory === ConsentCategory::Necessary) {
                    continue;
                }

                $record = ConsentRecord::query()->create([
                    'subject_type' => 'browser',
                    'subject_key_hash' => $data->subjectKeyHash,
                    'category' => $consentCategory,
                    'decision' => $decision,
                    'policy_version' => $data->policyVersion,
                    'source' => 'consent.preferences',
                    'ip_hash' => $data->ipHash,
                    'recorded_at' => now('UTC'),
                ]);
                $recordIds[] = (string) $record->getKey();
            }

            $this->audit->record(new AuditData(
                action: 'consent.changed',
                auditableType: ConsentRecord::class,
                auditableId: null,
                actorId: null,
                correlationId: $data->correlationId,
                afterHash: hash('sha256', json_encode($data->decisions, JSON_THROW_ON_ERROR)),
                metadata: [
                    'categories' => array_keys($data->decisions),
                    'policy_version' => $data->policyVersion,
                    'record_ids' => $recordIds,
                ],
                ipHash: $data->ipHash,
            ));
        }, attempts: 3);
    }
}
