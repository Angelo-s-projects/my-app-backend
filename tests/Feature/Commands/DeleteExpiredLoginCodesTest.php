<?php

namespace Tests\Feature\Commands;

use App\Models\LoginCode;
use Tests\TestCase;

class DeleteExpiredLoginCodesTest extends TestCase
{
    public function test_it_deletes_expired_login_codes(): void
    {
        $expiredCode = LoginCode::factory()
            ->for($this->createUser())
            ->create([
                'expires_at' => now()->subMinute(),
            ]);

        $validCode = LoginCode::factory()
            ->for($this->createUser())
            ->create([
                'expires_at' => now()->addDay(),
            ]);

        $this->artisan('login-codes:cleanup')
            ->expectsOutput('Deleted 1 expired login codes.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('login_codes', [
            'id' => $expiredCode->id,
        ]);

        $this->assertDatabaseHas('login_codes', [
            'id' => $validCode->id,
        ]);
    }
}
