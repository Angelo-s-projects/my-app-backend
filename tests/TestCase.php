<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getEndpoint(string $endpoint, int $status = 200): TestResponse
    {
        $response = $this->getJson($endpoint);

        $response->assertStatus($status);

        return $response;
    }
}