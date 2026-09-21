<?php

declare(strict_types=1);

namespace App\Enums;

enum SubmissionStatus: string
{
    case Received = 'received';
    case Scanning = 'scanning';
    case Triaged = 'triaged';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case AwaitingClient = 'awaiting_client';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Resolved = 'resolved';
    case NotProceeding = 'not_proceeding';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Quarantined = 'quarantined';
}
