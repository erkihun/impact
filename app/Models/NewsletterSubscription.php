<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NewsletterStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NewsletterSubscription extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => NewsletterStatus::class,
            'confirmation_sent_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'unsubscribed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ConsentRecord, $this> */
    public function consentRecord(): BelongsTo
    {
        return $this->belongsTo(ConsentRecord::class);
    }
}
