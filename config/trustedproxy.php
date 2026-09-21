<?php

declare(strict_types=1);

return [
    /*
    | Comma-separated IPs/CIDRs only. Never use "*" on an internet-facing
    | deployment because spoofed forwarding headers would become trusted.
    */
    'proxies' => env('TRUSTED_PROXIES'),
];
