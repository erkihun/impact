<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Privacy\ExecuteRetentionAction;
use App\Data\Privacy\ExecuteRetentionData;
use App\Enums\RetentionRunMode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class ApplyRetentionPoliciesCommand extends Command
{
    protected $signature = 'impact:retention:inspect
        {--execute : Execute approved anonymization instead of a dry run}
        {--approved-by= : UUID of the authorized privacy approver}
        {--limit=250 : Maximum candidates selected per record category}
        {--policy-version= : Approved retention policy version}';

    protected $description = 'Inspect or execute bounded, legal-hold-aware retention processing';

    public function handle(ExecuteRetentionAction $action): int
    {
        $mode = $this->option('execute') ? RetentionRunMode::Execute : RetentionRunMode::DryRun;
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1 || $limit > 500) {
            $this->error('The limit must be an integer between 1 and 500.');

            return self::INVALID;
        }

        $policyVersion = (string) ($this->option('policy-version')
            ?: config('impact.retention.policy_version'));
        if ($policyVersion === '') {
            $this->error('An approved retention policy version is required.');

            return self::INVALID;
        }

        try {
            $run = $action->execute(new ExecuteRetentionData(
                mode: $mode,
                policyVersion: $policyVersion,
                correlationId: (string) Str::uuid7(),
                approvedBy: $this->option('approved-by') ?: null,
                limit: $limit,
            ));
        } catch (AuthorizationException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Run', 'Mode', 'Candidates', 'Processed', 'Legal holds', 'Failures', 'Status'],
            [[
                $run->getKey(),
                $run->getRawOriginal('mode'),
                $run->candidate_count,
                $run->processed_count,
                $run->legal_hold_count,
                $run->failure_count,
                $run->getRawOriginal('status'),
            ]],
        );

        if ($mode === RetentionRunMode::DryRun) {
            $this->info('Dry run only. Re-run with --execute and --approved-by=<authorized-user-uuid>.');
        }

        return $run->failure_count === 0 ? self::SUCCESS : self::FAILURE;
    }
}
