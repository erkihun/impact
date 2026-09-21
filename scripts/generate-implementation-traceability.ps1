param(
    [string] $Root = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'
$implementationPath = Join-Path $Root 'docs/implementation/requirements-traceability.md'
$sddPath = Join-Path $Root 'tmp/docs/impact_Consulting_Organization_Website_SDD.txt'
$lldPath = Join-Path $Root 'tmp/docs/Impact_Consulting_Organization_Website_LLD.txt'
$sectionMark = [char]0x00A7

function Escape-Markdown([string] $Value)
{
    return $Value.Replace('|', '\|').Replace("`r", ' ').Replace("`n", ' ')
}

$existing = @{}
foreach ($line in Get-Content -Encoding utf8 $implementationPath) {
    if ($line -notmatch '^\| ([A-Z0-9]+(?:-[A-Z0-9]+)*-\d{3}) \|') {
        continue
    }

    $cells = @($line.Trim('|').Split('|') | ForEach-Object { $_.Trim() })
    if ($cells.Count -ge 13) {
        $existing[$cells[0]] = @{
            Summary = $cells[1]
            Status = $cells[10]
            Evidence = $cells[11]
            Tests = $cells[9]
        }
    } elseif ($cells.Count -ge 5) {
        $existing[$cells[0]] = @{
            Summary = $cells[1]
            Status = $cells[2]
            Evidence = $cells[3]
            Tests = $cells[4]
        }
    }
}

$sdd = @{}
foreach ($line in Get-Content -Encoding utf8 $sddPath) {
    if ($line -match '^\[R\d+\] ([A-Z0-9]+(?:-[A-Z0-9]+)*-\d{3}) \|\| (Must|Should|Could|Future) \|\| ([^|]+) \|\| ([^|]+) \|\| ([^|]+)$') {
        $sdd[$matches[1]] = @{
            Priority = $matches[2].Trim()
            Section = $matches[3].Trim()
            Component = $matches[4].Trim()
            Verification = $matches[5].Trim()
        }
    }
}

$lld = @{}
foreach ($line in Get-Content -Encoding utf8 $lldPath) {
    if ($line -match '^\[R\d+\] ([A-Z0-9]+(?:-[A-Z0-9]+)*-\d{3}) \|\| (Must|Should|Could|Future) \|\| ([^|]+) \|\| ([^|]+) \|\| ([^|]+) \|\| ([^|]+) \|\| ([^|]+)$') {
        $lld[$matches[1]] = @{
            Priority = $matches[2].Trim()
            Section = $matches[3].Trim()
            Module = $matches[4].Trim()
            DesignAction = $matches[5].Trim()
            DesignTest = $matches[6].Trim()
            Verification = $matches[7].Trim()
        }
    }
}

function Get-Implementation([string] $Id)
{
    switch -Regex ($Id) {
        '^FR-(GEN|HOM|ABT)-' {
            return @{
                Routes = '`/{locale}`, `/{locale}/about`, public catalog routes'
                Classes = '`HomeController`, `ContentController`, `ContentItem`, `ContentVersion`'
                Tables = '`content_items`, `content_versions`, catalog tables'
                Authorization = 'Public read filters; `ContentItemPolicy` and `content.*` permissions for authoring'
                Tests = '`LocalizationTest`, `SeoTest`, `ContentVersioningTest`'
                Evidence = 'Server-rendered bilingual pages plus immutable generic content workflow; structured leadership, credential and partner administration remains incomplete'
            }
        }
        '^FR-(SRV|IND)-' {
            return @{
                Routes = '`/{locale}/services*`, `/{locale}/industries*`'
                Classes = '`ServiceController`, `IndustryController`, `Service`, `Industry`, version models'
                Tables = '`services`, `service_versions`, `industries`, `industry_versions`, `service_industry`'
                Authorization = 'Published-locale query scopes; `services.manage` and `industries.manage` are catalogued'
                Tests = '`SeoTest`, `SearchReconciliationTest`, `LocalizationTest`'
                Evidence = 'Published localized public read paths and search projection are automated; catalog-specific admin CRUD is not complete'
            }
        }
        '^FR-EXP-' {
            return @{
                Routes = '`/{locale}/experts*`'
                Classes = '`ExpertController`, `Expert`, `ExpertVersion`'
                Tables = '`experts`, `expert_versions`, `expert_service`'
                Authorization = 'Published/consent query controls; `experts.manage` is catalogued'
                Tests = '`SearchReconciliationTest`, `LocalizationTest`'
                Evidence = 'Public consent-aware profiles exist; verification workflow and complete administration remain partial'
            }
        }
        '^FR-CAS-' {
            return @{
                Routes = '`/{locale}/case-studies*`'
                Classes = '`CaseStudyController`, `CaseStudy`, `CaseStudyVersion`'
                Tables = '`case_studies`, `case_study_versions`'
                Authorization = 'Published/client-consent query controls; `case-studies.manage` is catalogued'
                Tests = '`SearchReconciliationTest`, `SeoTest`'
                Evidence = 'Consent-aware public case-study reads exist; legal/client review administration remains partial'
            }
        }
        '^FR-(INS|NEW)-' {
            return @{
                Routes = '`/{locale}/insights*`'
                Classes = '`InsightController`, `Insight`, `InsightVersion`'
                Tables = '`insights`, `insight_versions`, `media_assets`'
                Authorization = 'Published/expiry query controls; `insights.manage` is catalogued'
                Tests = '`SearchReconciliationTest`, `SeoTest`, `LocalizationTest`'
                Evidence = 'Localized published insight/news reads exist; dedicated publication/report administration remains partial'
            }
        }
        '^FR-EVT-' {
            return @{
                Routes = '`/{locale}/events*`, `POST /{locale}/events/{slug}/registrations`'
                Classes = '`EventController`, `EventRegistrationController`, `RegisterForEventAction`, `Event`'
                Tables = '`events`, `event_registrations`, `consent_records`'
                Authorization = 'Public capacity/state guards; `events.manage` and `EventRegistrationPolicy`'
                Tests = '`EventRegistrationTest`, `QueuedAcknowledgementsTest`'
                Evidence = 'Atomic capacity enforcement, consent and acknowledgement are tested; event administration/export remain partial'
            }
        }
        '^FR-CAR-' {
            return @{
                Routes = '`/{locale}/careers*`, application POST, `/admin/applications*`, signed file download'
                Classes = '`ApplicationController`, `SubmitApplicationAction`, `ChangeApplicationStatusAction`, `ApplicationPolicy`'
                Tables = '`vacancies`, `applications`, `application_files`, `application_status_histories`, `media_assets`'
                Authorization = '`applications.view`, `applications.update-status`, `ApplicationPolicy`'
                Tests = '`ApplicationSubmissionTest`, `ApplicationOperationsTest`, `PrivateDownloadAuditTest`'
                Evidence = 'Closed-window validation, quarantined CV, HR isolation, history, anonymization and audited signed download are tested'
            }
        }
        '^FR-LEA-' {
            return @{
                Routes = 'Consultation, RFP and contact GET/POST routes; `/admin/engagement*`; signed submission-file download'
                Classes = '`CreateEngagementSubmissionAction`, `UpdateSubmissionAction`, `SubmissionFileDownloadController`, `EngagementSubmissionPolicy`'
                Tables = '`engagement_submissions`, `engagement_submission_histories`, `submission_files`, `media_assets`, `consent_records`'
                Authorization = '`engagement.view`, `engagement.assign`, `engagement.update-status`, object policy'
                Tests = '`EngagementSubmissionTest`, `EngagementOperationsTest`, `RfpAttachmentSecurityTest`, `WorkflowConflictResponseTest`'
                Evidence = 'Distinct intake types, opaque quarantined attachments, initial and privileged history, object-scoped signed download and 409 transitions are automated'
            }
        }
        '^FR-CON-' {
            return @{
                Routes = '`/{locale}/contact`, contact POST; office public/admin routes not complete'
                Classes = '`EngagementSubmissionController`, `CreateEngagementSubmissionAction`, `Office`'
                Tables = '`offices`, `engagement_submissions`'
                Authorization = 'Public intake plus `EngagementSubmissionPolicy`; office administration permission not implemented'
                Tests = '`RfpAttachmentSecurityTest`, `EngagementSubmissionTest`'
                Evidence = 'Contact intake and office schema exist; reusable office presentation and management remain incomplete'
            }
        }
        '^FR-SCH-' {
            return @{
                Routes = '`/{locale}/search`, `/api/v1/search/suggestions`'
                Classes = '`SearchController`, `SearchIndexReconciler`, `DatabaseSearchIndexer`, search jobs/command'
                Tables = '`search_documents`, `search_synonyms`, `search_query_logs`'
                Authorization = 'Published parent/locale filters; `search.manage` catalogued'
                Tests = '`SearchPrivacyAndIndexingTest`, `SearchReconciliationTest`'
                Evidence = 'Private-query hashing, stable database ranking and stale-index reconciliation are automated; external provider acceptance remains'
            }
        }
        '^FR-LNG-' {
            return @{
                Routes = 'All `/{locale}` routes and locale-segmented sitemaps'
                Classes = '`SetLocale`, localized version models, public controllers'
                Tables = '`locales`, localized version tables, `content_slugs`'
                Authorization = 'Translation permissions catalogued; published locale filters'
                Tests = '`LocalizationTest`, `SeoTest`, `SearchReconciliationTest`'
                Evidence = 'English/Amharic routing, UI catalogue, hreflang and locale-aware reads are tested; readiness governance remains partial'
            }
        }
        '^FR-(CMS|WFL)-' {
            return @{
                Routes = '`/admin/content*`, signed preview, workflow transitions and rollback'
                Classes = '`CreateContentAction`, `UpdateContentAction`, `TransitionContentWorkflowAction`, `PublishScheduledContentJob`, `ContentItemPolicy`'
                Tables = '`content_items`, `content_versions`, `content_slugs`, `workflow_events`, `publication_schedules`'
                Authorization = '`content.view/create/update/submit/approve/publish/rollback`, `ContentItemPolicy`'
                Tests = '`ContentManagementTest`, `ContentVersioningTest`, `ScheduledPublicationLifecycleTest`, `ContentWorkflowTest`'
                Evidence = 'Immutable revisions, concurrency conflicts, independent approval, scheduling, automated publication/unpublication and workflow evidence are tested'
            }
        }
        '^FR-MED-' {
            return @{
                Routes = '`/admin/media*` and three object-scoped signed download routes'
                Classes = '`QuarantineUploadAction`, `ScanMediaAssetJob`, `ProcessMediaAssetJob`, `ApproveMediaAction`, media policies/download controllers'
                Tables = '`media_assets`, `media_variants`, `application_files`, `submission_files`'
                Authorization = '`media.*` plus application/engagement object policies; linked submission assets excluded from media library'
                Tests = '`MediaLifecycleTest`, `RfpAttachmentSecurityTest`, `PrivateDownloadAuditTest`'
                Evidence = 'Signature-derived MIME checks, UUIDv7-only keys, quarantine, safe variants, cross-module isolation and audited signed delivery are automated'
            }
        }
        '^FR-(ANA|NTF)-' {
            return @{
                Routes = '`POST /consent`, newsletter lifecycle and public submission routes'
                Classes = '`RecordConsentAction`, newsletter Actions, queued Notifications, acknowledgement Actions'
                Tables = '`consent_records`, `newsletter_subscriptions`, `analytics_event_outbox`'
                Authorization = 'Version-locked public consent; signed newsletter tokens; notification data minimization'
                Tests = '`ConsentGatingTest`, `DoubleOptInTest`, `QueuedAcknowledgementsTest`'
                Evidence = 'Append-only consent and localized queued acknowledgements are tested; provider delivery outcomes and analytics adapter remain external/partial'
            }
        }
        '^FR-(RBA|ADM)-' {
            return @{
                Routes = '`/admin/users*`, `/admin/roles*`, `/admin/settings`, `/admin/audit-events`, `/ready`'
                Classes = 'Identity Actions, MFA/session middleware, role guard, Policies, `DatabaseAuditRecorder`, `HealthController`'
                Tables = '`users`, `roles`, `permissions`, pivots, `audit_events`, `security_events`, `settings`'
                Authorization = 'Authentication, active session, verified email, MFA, permission middleware and object policies'
                Tests = 'Identity/MFA/settings/audit/security feature tests'
                Evidence = 'Invitation-only identity, delegated ceilings, session revocation, MFA, immutable audit and typed settings have automated evidence'
            }
        }
        '^DR-RET-' {
            return @{
                Routes = 'Authorized retention command; no public route'
                Classes = '`ExecuteRetentionAction`, retention/cleanup commands, `LegalHold`, deletion jobs'
                Tables = '`retention_runs`, `legal_holds`, personal-data and media tables'
                Authorization = '`privacy.retention.execute` plus explicit configuration approval'
                Tests = '`RetentionProcessingTest`, `PrivacyCleanupCommandTest`'
                Evidence = 'Bounded dry-run/execute, legal holds, anonymization and hashed evidence are automated; approved target policy remains external'
            }
        }
        '^DR-DQ-' {
            return @{
                Routes = 'Applies across named application routes'
                Classes = 'Form Requests, typed DTOs/Actions, Eloquent models and migrations'
                Tables = 'All domain tables'
                Authorization = 'Module permission and policy controls'
                Tests = 'Relevant feature, workflow and migration tests'
                Evidence = 'Database constraints and typed transactional writes exist, with module-specific gaps retained as Partial'
            }
        }
        '^(DR-MIG|DOC|TRN)-' {
            return @{
                Routes = 'None'
                Classes = 'No complete application implementation'
                Tables = 'None or source-dependent'
                Authorization = 'Not applicable / not implemented'
                Tests = 'None'
                Evidence = 'Backlog or governance deliverable; no completion claim'
            }
        }
        '^IR-' {
            return @{
                Routes = 'Provider-facing behavior is invoked through domain Actions/jobs'
                Classes = 'Laravel mail/storage/search abstractions and local adapters'
                Tables = 'Outbox, media, search and domain evidence tables as applicable'
                Authorization = 'Adapter calls occur after domain validation and authorization'
                Tests = 'Contract/fake coverage in relevant feature tests'
                Evidence = 'Local adapters/fakes exist; live provider, failure-mode and target credential acceptance remain external'
            }
        }
        '^NFR-A11Y-' {
            return @{
                Routes = 'Public and administrative web routes'
                Classes = 'Blade layouts/components, Tailwind styles, Form Requests'
                Tables = 'Not applicable'
                Authorization = 'Not applicable'
                Tests = '`LocalizationTest`, browser viewport evidence; independent WCAG audit pending'
                Evidence = 'Skip link, landmarks, labels, error summaries, focus styles and responsive layouts exist; independent assistive-technology acceptance remains'
            }
        }
        '^NFR-SEC-' {
            return @{
                Routes = 'All web/API routes; `/up`, `/ready`'
                Classes = 'Security middleware, Form Requests, Policies, secure-file Actions, session/MFA controls'
                Tables = '`security_events`, `audit_events`, sessions, consent and restricted domain tables'
                Authorization = 'Deny-by-default middleware/policies and signed object-scoped delivery'
                Tests = 'Security headers/configuration, route protection, MFA, IDOR/private-download and dependency audit evidence'
                Evidence = 'Application-controlled high-risk controls have automated coverage; DAST/penetration and target edge evidence remain external'
            }
        }
        '^NFR-' {
            return @{
                Routes = 'Cross-cutting public/admin routes and health endpoints'
                Classes = 'Controllers, middleware, jobs, caching/search abstractions and deployment configuration'
                Tables = 'Applicable domain, cache, queue and monitoring evidence tables'
                Authorization = 'Cross-cutting'
                Tests = 'Relevant feature tests plus static/build gates'
                Evidence = 'Local application evidence exists; independent performance, compatibility, availability and observability acceptance remains'
            }
        }
        '^OPS-' {
            return @{
                Routes = '`/up`, `/ready`; operational commands and scheduler'
                Classes = 'Health controller, scheduled commands/jobs, deployment worker and web configuration'
                Tables = 'Jobs, failed jobs, cache/session, retention and domain tables'
                Authorization = 'Production operator controls and application permissions where applicable'
                Tests = 'Configuration, scheduler, retention and command tests; target drills pending'
                Evidence = 'Deployment templates and application commands exist; live infrastructure, backup/restore and incident drills are external gates'
            }
        }
        default {
            return @{
                Routes = 'See module audit'
                Classes = 'See current source inventory'
                Tables = 'See migration inventory'
                Authorization = 'See permission catalogue/policies'
                Tests = 'See test coverage matrix'
                Evidence = 'Conservative partial implementation evidence; see current-state-audit.md'
            }
        }
    }
}

$statusOverrides = @{
    'FR-LEA-013' = 'Verified'
    'FR-MED-005' = 'Verified'
    'FR-WFL-006' = 'Verified'
    'FR-WFL-008' = 'Verified'
}

$rows = [System.Collections.Generic.List[string]]::new()
foreach ($id in $existing.Keys | Sort-Object) {
    $source = $existing[$id]
    $design = $sdd[$id]
    $detail = $lld[$id]
    $implementation = Get-Implementation $id
    $status = if ($statusOverrides.ContainsKey($id)) { $statusOverrides[$id] } else { $source.Status }
    $defect = switch ($status) {
        'Verified' { 'None in automated local scope; target-environment and independent acceptance gates remain tracked separately.' }
        'Planned' { 'No verified implementation. See `module-plan.md` and `unresolved-dependencies.md`.' }
        default { 'One or more acceptance dimensions remain unverified. See the matching module in `current-state-audit.md`.' }
    }

    if ($id -eq 'FR-WFL-007') {
        $defect = 'Scheduled unpublication is automated; automatic redirect creation/validation for the expired URL remains incomplete.'
    } elseif ($id -eq 'FR-LEA-004') {
        $defect = 'Attachment quarantine and authorization are automated; live malware-provider acceptance remains external.'
    } elseif ($id -eq 'FR-MED-005') {
        $defect = 'No local application defect; target S3 bucket-policy and scanner acceptance remain external.'
    }

    $sddCell = if ($null -ne $design) {
        "$sectionMark$($design.Section) $($design.Component)"
    } else {
        'Not mapped in SDD formal matrix'
    }
    $lldCell = if ($null -ne $detail) {
        "$sectionMark$($detail.Section) $($detail.Module); design action: $($detail.DesignAction)"
    } else {
        'Not mapped in LLD formal matrix'
    }
    $priority = if ($null -ne $detail) { $detail.Priority } elseif ($null -ne $design) { $design.Priority } else { 'Unmapped' }

    $cells = @(
        $id,
        $source.Summary,
        $priority,
        $sddCell,
        $lldCell,
        $implementation.Routes,
        $implementation.Classes,
        $implementation.Tables,
        $implementation.Authorization,
        $implementation.Tests,
        $status,
        $implementation.Evidence,
        $defect
    ) | ForEach-Object { Escape-Markdown ([string] $_) }
    $rows.Add('| ' + ($cells -join ' | ') + ' |')
}

$counts = $rows | ForEach-Object { ($_ -split '\|')[11].Trim() } | Group-Object
$countText = ($counts | Sort-Object Name | ForEach-Object { "$($_.Name): $($_.Count)" }) -join '; '
$header = @(
    '# Requirements traceability matrix',
    '',
    'Generated from the 379-ID SRS inventory and joined to the formal SDD/LLD matrices. Design artifacts are labelled as design; implementation columns name only current repository evidence.',
    '',
    "Status summary: $countText.",
    '',
    '| Requirement ID | Requirement summary | Priority | SDD design section | LLD design section | Implementing routes | Implementing classes | Database tables | Permission or policy | Test identifiers | Current status | Evidence | Remaining defect |',
    '|---|---|---|---|---|---|---|---|---|---|---|---|---|'
)

Set-Content -Encoding utf8 $implementationPath -Value @($header + $rows)
Write-Output "Wrote $($rows.Count) traceability rows to $implementationPath"
