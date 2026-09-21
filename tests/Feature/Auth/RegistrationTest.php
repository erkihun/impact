<?php

declare(strict_types=1);

test('public staff registration is not exposed', function (): void {
    $this->get('/register')->assertNotFound();
});

test('posting to the obsolete registration path cannot create an account', function (): void {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertMethodNotAllowed();

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});
