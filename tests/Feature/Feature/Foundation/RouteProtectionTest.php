<?php

declare(strict_types=1);

use App\Models\User;

it('requires authentication for administration routes', function (): void {
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/admin/audit-events')->assertRedirect('/login');
});

it('requires verified email before entering administration', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/admin')->assertRedirect('/verify-email');
});
