<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Audit\AuditData;
use App\Models\AuditEvent;

interface AuditRecorder
{
    public function record(AuditData $data): AuditEvent;
}
