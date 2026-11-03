<?php

namespace Tests\Feature\Authentication;

use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendLoginCodeTest extends TestCase
{
    private const SEND_ENDPOINT = '/api/send-login-code';

    private const LOGIN_ENDPOINT = '/api/login';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->user = $this->createUser();
    }

    public function test_it_sends_a_five_digit_login_code(): void
    {
        $this->sendLoginCode();

        $this->assertDatabaseHas('login_codes', [
            'user_id' => $this->user->id,
        ]);

        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) {
            return preg_match('/^\d{5}$/', $mail->code) === 1;
        });
    }

    public function test_it_does_not_send_a_code_for_an_unknown_email(): void
    {
        $this->postEndpoint(self::SEND_ENDPOINT, [
            'email' => fake()->unique()->safeEmail(),
        ]);

        $this->assertDatabaseCount('login_codes', 0);

        Mail::assertNothingSent();
    }

    public function test_it_replaces_an_existing_login_code(): void
    {
        $this->sendLoginCode();
        $this->sendLoginCode();

        $this->assertDatabaseCount('login_codes', 1);

        $this->assertDatabaseHas('login_codes', [
            'user_id' => $this->user->id,
            'resend_attempts' => 1,
        ]);

        Mail::assertSent(LoginCodeMail::class, 2);
    }

    public function test_it_blocks_sending_after_three_resends(): void
    {
        $this->sendLoginCode();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->sendLoginCode();
        }

        $this->postEndpoint(self::SEND_ENDPOINT, [
            'email' => $this->user->email,
        ], 429);

        $this->assertDatabaseHas('login_codes', [
            'user_id' => $this->user->id,
            'resend_attempts' => 3,
        ]);

        $this->assertNotNull(
            $this->user->loginCode()->first()->blocked_until
        );
    }

    public function test_it_allows_sending_again_after_the_cooldown(): void
    {
        $this->sendLoginCode();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->sendLoginCode();
        }

        $this->postEndpoint(self::SEND_ENDPOINT, [
            'email' => $this->user->email,
        ], 429);

        $this->travel(5)->minutes();

        $this->sendLoginCode();

        $this->assertDatabaseHas('login_codes', [
            'user_id' => $this->user->id,
            'resend_attempts' => 1,
            'blocked_until' => null,
        ]);
    }

    public function test_resending_a_code_invalidates_the_previous_code(): void
    {
        $oldCode = fake()->numerify('#####');

        LoginCode::factory()
            ->for($this->user)
            ->withCode($oldCode)
            ->create([
                'expires_at' => now()->addMinutes(5),
            ]);

        $this->sendLoginCode();

        $this->postEndpoint(self::LOGIN_ENDPOINT, [
            'code' => $oldCode,
        ], 422);
    }

    private function sendLoginCode(): void
    {
        $this->postEndpoint(self::SEND_ENDPOINT, [
            'email' => $this->user->email,
        ]);
    }
}
