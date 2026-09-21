<?php

declare(strict_types=1);

namespace App\Enums;

enum ApplicationStatus: string
{
    case Received = 'received';
    case Screening = 'screening';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Offered = 'offered';
    case Hired = 'hired';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Anonymized = 'anonymized';
}
