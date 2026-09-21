<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PageComposition;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class PageCompositionPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public PageComposition $composition) {}
}
