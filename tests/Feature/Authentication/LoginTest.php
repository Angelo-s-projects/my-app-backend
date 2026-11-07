<?php

namespace Tests\Feature\Authentication;

use App\Models\LoginCode;
use App\Models\User;
use Tests\TestCase;

class LoginTest extends TestCase
{
    private const LOGIN_ENDPOINT = '/api/login';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
    }

    public function test_it_rejects_an_expired_login_code(): void
    {
        $code = fake()->numerify('#####');

        LoginCode::factory()
            ->for($this->user)
            ->withCode($code)
            ->create([
                'expires_at' => now()->subMinute(),
            ]);

        $this->postEndpoint(self::LOGIN_ENDPOINT, [
            'email' => $this->user->email,
            'code' => $code,
        ], 422);

        $this->assertDatabaseHas('login_codes', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_it_logs_in_with_a_valid_code(): void
    {
        $code = fake()->numerify('#####');

        LoginCode::factory()
            ->for($this->user)
            ->withCode($code)
            ->create([
                'expires_at' => now()->addMinutes(
                    config('authorization.login_code.expiry_minutes')
                ),
            ]);

        $response = $this->postEndpoint(self::LOGIN_ENDPOINT, [
            'email' => $this->user->email,
            'code' => $code,
        ]);

        $response->assertJsonStructure([
            'code',
            'data' => [
                'token',
            ],
        ]);

        $response->assertJsonPath('code', 'LOGIN_SUCCESSFUL');

        $this->assertDatabaseMissing('login_codes', [
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_it_rejects_an_incorrect_login_code(): void
    {
        $validCode = fake()->numerify('#####');

        do {
            $incorrectCode = fake()->numerify('#####');
        } while ($incorrectCode === $validCode);

        LoginCode::factory()
            ->for($this->user)
            ->withCode($validCode)
            ->create([
                'expires_at' => now()->addMinutes(
                    config('authorization.login_code.expiry_minutes')
                ),
            ]);

        $this->postEndpoint(self::LOGIN_ENDPOINT, [
            'email' => $this->user->email,
            'code' => $incorrectCode,
        ], 422);

        $this->assertDatabaseHas('login_codes', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_it_rejects_a_valid_code_with_an_incorrect_email(): void
    {
        $code = fake()->numerify('#####');

        LoginCode::factory()
            ->for($this->user)
            ->withCode($code)
            ->create([
                'expires_at' => now()->addMinutes(
                    config('authorization.login_code.expiry_minutes')
                ),
            ]);

        $this->postEndpoint(self::LOGIN_ENDPOINT, [
            'email' => fake()->safeEmail(),
            'code' => $code,
        ], 422);

        $this->assertDatabaseHas('login_codes', [
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_login_code_cannot_be_reused(): void
    {
        $code = fake()->numerify('#####');

        LoginCode::factory()
            ->for($this->user)
            ->withCode($code)
            ->create([
                'expires_at' => now()->addMinutes(
                    config('authorization.login_code.expiry_minutes')
                ),
            ]);

        $this->postEndpoint(self::LOGIN_ENDPOINT, [
            'email' => $this->user->email,
            'code' => $code,
        ]);

        $this->postEndpoint(self::LOGIN_ENDPOINT, [
            'email' => $this->user->email,
            'code' => $code,
        ], 422);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }
}
