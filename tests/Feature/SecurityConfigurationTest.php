<?php

declare(strict_types=1);

it('applies browser security headers and prevents privileged response caching', function (): void {
    $this->get('/en')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
        ->assertHeader('Cross-Origin-Resource-Policy', 'same-site')
        ->assertHeader('Content-Security-Policy');

    $response = $this->get('/admin/');
    $response
        ->assertRedirect()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('emits HSTS only for secure requests', function (): void {
    $this->get('/en')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/en')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('defaults queued work to after-commit dispatch', function (): void {
    expect(config('queue.connections.database.after_commit'))->toBeTrue()
        ->and(config('queue.connections.redis.after_commit'))->toBeTrue()
        ->and(config('queue.connections.sqs.after_commit'))->toBeTrue()
        ->and(config('filesystems.disks.local.throw'))->toBeTrue();
});
