<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_and_login()
    {
        $response = $this->postJson('/api/mobile/register', [
            'phone' => '256700000000',
            'name' => 'Test User',
            'pin' => '1234'
        ]);

    // assert register response

        $response->assertStatus(201)->assertJsonStructure(['user' => ['id', 'name'], 'token']);

        $login = $this->postJson('/api/mobile/login', ['phone' => '256700000000', 'pin' => '1234']);

    // assert login response

        $login->assertStatus(200)->assertJsonStructure(['user' => ['id', 'name'], 'token']);
    }
}
