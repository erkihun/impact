<?php

declare(strict_types=1);

it('adds baseline browser security headers to public responses', function (): void {
    $this->get('/en')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Content-Security-Policy');
});

it('reports truthful dependency readiness', function (): void {
    $this->getJson('/ready')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ready',
            'checks' => [
                'database' => true,
                'cache' => true,
                'storage' => true,
            ],
        ]);
});
