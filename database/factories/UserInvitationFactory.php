<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<UserInvitation> */
final class UserInvitationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'invited_by' => User::factory(),
            'token_hash' => hash('sha256', Str::random(64)),
            'role_ids' => [],
            'locale' => 'en',
            'expires_at' => now('UTC')->addDays(7),
        ];
    }
}
