<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getEndpoint(
        string $endpoint,
        int $status = 200
    ): TestResponse {
        $response = $this->getJson($endpoint);

        $response->assertStatus($status);

        return $response;
    }

    protected function postEndpoint(
        string $endpoint,
        array $data = [],
        int $status = 200
    ): TestResponse {
        $response = $this->postJson($endpoint, $data);

        $response->assertStatus($status);

        return $response;
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
        ], $attributes));
    }

    protected function createLoggedInUser(array $attributes = []): User
    {
        $user = $this->createUser($attributes);
        $this->actingAs($user, 'sanctum');

        return $user;
    }
}
