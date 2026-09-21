<?php

declare(strict_types=1);

use App\Enums\ConsentCategory;
use App\Models\AuditEvent;
use App\Models\ConsentRecord;

it('records versioned non-essential browser consent as append-only evidence', function (): void {
    $this->postJson(route('consent.update'), [
        'decisions' => [
            'necessary' => false,
            'analytics' => true,
            'marketing' => false,
        ],
        'policy_version' => config('impact.privacy.policy_version'),
    ])->assertOk()->assertJson(['recorded' => true]);

    expect(ConsentRecord::query()->count())->toBe(2)
        ->and(ConsentRecord::query()->where('category', ConsentCategory::Necessary->value)->exists())->toBeFalse()
        ->and(ConsentRecord::query()->where('category', ConsentCategory::Analytics->value)->value('decision'))->toBeTrue()
        ->and(ConsentRecord::query()->where('category', ConsentCategory::Marketing->value)->value('decision'))->toBeFalse()
        ->and(AuditEvent::query()->where('action', 'consent.changed')->exists())->toBeTrue();
});

it('rejects unknown consent categories and stale policy versions', function (): void {
    $this->postJson(route('consent.update'), [
        'decisions' => ['unknown' => true],
        'policy_version' => config('impact.privacy.policy_version'),
    ])->assertUnprocessable()->assertJsonValidationErrors('decisions');

    $this->postJson(route('consent.update'), [
        'decisions' => ['analytics' => true],
        'policy_version' => 'outdated',
    ])->assertUnprocessable()->assertJsonValidationErrors('policy_version');

    expect(ConsentRecord::query()->count())->toBe(0);
});
