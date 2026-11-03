<?php

namespace Database\Factories;

use App\Models\LoginCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<LoginCode>
 */
class LoginCodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => Hash::make(fake()->numerify('#####')),
            'expires_at' => now()->addMinutes(5),
            'resend_attempts' => 0,
            'blocked_until' => null,
        ];
    }

    public function withCode(string $code): static
    {
        return $this->state(fn () => [
            'code' => Hash::make($code),
        ]);
    }
}
