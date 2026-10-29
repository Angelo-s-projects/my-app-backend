<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    private const REGISTER_ENDPOINT = '/api/register';

    private array $registrationData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registrationData = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }

    public function test_user_can_register(): void
    {
        $this->postEndpoint(
            self::REGISTER_ENDPOINT,
            $this->registrationData,
            
            201
        )->assertExactJson([
            'code' => 'REGISTRATION_SUCCESSFUL',
        ]);

        $this->assertDatabaseHas('users', $this->registrationData);
        $this->assertDatabaseCount('users', 1);
    }

    #[DataProvider('invalidRegistrationDataProvider')]
    public function test_registration_rejects_invalid_data(
        array $overrides,
        string $expectedError
    ): void {
        $this->postEndpoint(
            self::REGISTER_ENDPOINT,
            array_merge($this->registrationData, $overrides),
            422
        )->assertJsonValidationErrors($expectedError);

        $this->assertDatabaseCount('users', 0);
    }

    public static function invalidRegistrationDataProvider(): array
    {
        return [
            'missing first name' => [
                ['first_name' => ''],
                'first_name',
            ],
            'missing last name' => [
                ['last_name' => ''],
                'last_name',
            ],
            'missing email' => [
                ['email' => ''],
                'email',
            ],
            'invalid email' => [
                ['email' => 'not-an-email'],
                'email',
            ],
        ];
    }

    public function test_registration_rejects_an_existing_email(): void
    {
        $user = User::factory()->create();

        $this->postEndpoint(
            self::REGISTER_ENDPOINT,
            array_merge($this->registrationData, [
                'email' => $user->email,
            ]),
            422
        )->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
    }
}
