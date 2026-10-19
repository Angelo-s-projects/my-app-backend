<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    public function test_get_users_returns_200(): void
    {
        $this->getEndpoint('/api/users');
    }

    public function test_get_users_returns_paginated_users(): void
    {
        User::factory()->count(20)->create();

        $response = $this->getEndpoint('/api/users');

        $response->assertJsonCount(15, 'data')
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 15)
            ->assertJsonPath('total', 20);
    }

    public function test_get_users_returns_expected_user_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->getEndpoint('/api/users');

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ],
            ],
        ]);

        $response->assertJsonFragment([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ]);
    }

    public function test_get_users_returns_empty_data_when_no_users_exist(): void
    {
        $response = $this->getEndpoint('/api/users');

        $response->assertJsonCount(0, 'data')
            ->assertJsonPath('total', 0);
    }
}
