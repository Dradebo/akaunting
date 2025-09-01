<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_transaction()
    {
        $register = $this->postJson('/api/mobile/register', [
            'phone' => '256700000001',
            'name' => 'Trader',
            'pin' => '4321'
        ]);

        $token = $register->json('token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/transactions', [
                'client_id' => '00000000-0000-0000-0000-000000000001',
                'type' => 'income',
                'amount_minor' => 10000,
                'date' => now()->toDateString(),
                'notes' => 'Test sale'
            ]);

    // assert create response
        $response->assertStatus(201)->assertJsonStructure(['id', 'client_id']);

        $list = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/mobile/transactions');
        $list->assertStatus(200);
    }
}
