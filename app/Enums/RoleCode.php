<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleCode: string
{
    case Contributor = 'contributor';
    case Editor = 'editor';
    case Translator = 'translator';
    case Reviewer = 'reviewer';
    case Publisher = 'publisher';
    case EngagementOfficer = 'engagement_officer';
    case RecruitmentOfficer = 'recruitment_officer';
    case Administrator = 'administrator';
    case SecurityAuditor = 'security_auditor';
    case SuperAdministrator = 'super_administrator';
}
