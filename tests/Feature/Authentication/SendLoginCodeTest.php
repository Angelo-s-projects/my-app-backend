<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendLoginCodeTest extends TestCase
{
    private const SEND_LOGIN_CODE_ENDPOINT = '/api/send-login-code';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_it_sends_a_five_digit_login_code_to_the_users_email(): void
    {
        $response = $this->postJson(self::SEND_LOGIN_CODE_ENDPOINT, [
            'email' => $this->user->email,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('login_codes', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_it_does_not_send_a_login_code_when_email_does_not_exist(): void
    {
        Mail::fake();

        $response = $this->postJson(self::SEND_LOGIN_CODE_ENDPOINT, [
            'email' => 'unknown@example.com',
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('login_codes', 0);

        Mail::assertNothingSent();
    }

    public function test_it_replaces_an_existing_login_code(): void
    {
        // TODO
    }

    public function test_it_blocks_resending_after_three_attempts(): void
    {
        // TODO
    }

    public function test_it_allows_resending_after_the_five_minute_cooldown(): void
    {
        // TODO
    }

    public function test_it_rejects_an_expired_login_code(): void
    {
        // TODO
    }

    public function test_it_logs_in_with_a_valid_login_code(): void
    {
        // TODO
    }

    public function test_it_rejects_an_incorrect_login_code(): void
    {
        // TODO
    }

    public function test_it_cannot_reuse_a_login_code_after_successful_login(): void
    {
        // TODO
    }
}