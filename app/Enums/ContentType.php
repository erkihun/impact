<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentType: string
{
    case Page = 'page';
    case Service = 'service';
    case Industry = 'industry';
    case Expert = 'expert';
    case CaseStudy = 'case_study';
    case Insight = 'insight';
    case Event = 'event';
    case Vacancy = 'vacancy';
    case Office = 'office';
    case LegalDocument = 'legal_document';
}
