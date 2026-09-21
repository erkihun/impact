<?php

declare(strict_types=1);

use App\Models\EngagementSubmission;

it('accepts a privacy-acknowledged consultation and records consent and audit evidence', function (): void {
    $response = $this->post('/en/consultation-requests', [
        'type' => 'consultation',
        'contact_name' => 'Aster Bekele',
        'organization_name' => 'Example Institution',
        'email' => 'ASTER@EXAMPLE.COM',
        'description' => 'We need support designing a practical institutional transformation roadmap.',
        'privacy_acknowledged' => '1',
        'policy_version' => '2026-07-26',
    ]);

    $response->assertRedirect()->assertSessionHas('submission.reference');

    $submission = EngagementSubmission::query()->sole();
    expect($submission->email)->toBe('aster@example.com')
        ->and($submission->description_encrypted)->toContain('institutional transformation');

    $this->assertDatabaseCount('consent_records', 1);
    $this->assertDatabaseHas('audit_events', ['action' => 'engagement.received']);
});

it('does not accept a consultation without privacy acknowledgement', function (): void {
    $this->from('/en/consultation')->post('/en/consultation-requests', [
        'type' => 'consultation',
        'contact_name' => 'Aster Bekele',
        'email' => 'aster@example.com',
        'description' => 'This description is long enough for the validation contract.',
    ])->assertRedirect('/en/consultation')->assertSessionHasErrors('privacy_acknowledged');
});
