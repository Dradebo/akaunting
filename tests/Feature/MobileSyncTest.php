<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_sync_idempotent_create()
    {
        $register = $this->postJson('/api/mobile/register', [
            'phone' => '256700000002',
            'name' => 'Sync User',
            'pin' => '0000'
        ]);

        $token = $register->json('token');

        $records = [];
        $records[] = [
            'client_id' => '11111111-1111-1111-1111-111111111111',
            'op' => 'create',
            'payload' => [
                'type' => 'income',
                'amount_minor' => 5000,
                'date' => now()->toDateString(),
                'notes' => 'Sync test'
            ]
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev1', 'records' => $records]);

    // assert sync response
        $response->assertStatus(200)->assertJsonStructure(['applied', 'conflicts']);

        // run again - should not duplicate
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev1', 'records' => $records]);

        $response2->assertStatus(200);
        $this->assertEquals($response->json('applied')[0]['server_id'], $response2->json('applied')[0]['server_id']);
    }

    public function test_update_and_conflict_resolution()
    {
        $register = $this->postJson('/api/mobile/register', [
            'phone' => '256700000003',
            'name' => 'Sync Updater',
            'pin' => '1111'
        ]);

        $token = $register->json('token');

        // create a record first
        $client_id = '22222222-2222-2222-2222-222222222222';
        $records = [[
            'client_id' => $client_id,
            'op' => 'create',
            'payload' => [
                'type' => 'income',
                'amount_minor' => 7000,
                'date' => now()->toDateString(),
                'notes' => 'Initial'
            ]
        ]];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev2', 'records' => $records]);

        $server_id = $response->json('applied')[0]['server_id'];

        // Simulate server-side update to create a conflict
        \App\Models\Banking\Transaction::where('id', $server_id)->update(['notes' => 'Server edit']);

        // Now attempt client update with older timestamp
        $olderTs = now()->subMinutes(10)->toDateTimeString();
        $records = [[
            'client_id' => $client_id,
            'op' => 'update',
            'payload' => [
                'amount_minor' => 8000,
                'notes' => 'Client edit',
                'client_updated_at' => $olderTs
            ]
        ]];

        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev2', 'records' => $records]);

        $response2->assertStatus(200);
        $this->assertNotEmpty($response2->json('conflicts'));
    }

    public function test_delete_operation()
    {
        $register = $this->postJson('/api/mobile/register', [
            'phone' => '256700000004',
            'name' => 'Sync Deleter',
            'pin' => '2222'
        ]);

        $token = $register->json('token');

        $client_id = '33333333-3333-3333-3333-333333333333';
        $records = [[
            'client_id' => $client_id,
            'op' => 'create',
            'payload' => [
                'type' => 'expense',
                'amount_minor' => 2000,
                'date' => now()->toDateString(),
                'notes' => 'To be deleted'
            ]
        ]];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev3', 'records' => $records]);

        $server_id = $response->json('applied')[0]['server_id'];

        $del = [[
            'client_id' => $client_id,
            'op' => 'delete',
            'payload' => []
        ]];

        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev3', 'records' => $del]);

        $response2->assertStatus(200);
        $this->assertEquals('delete', $response2->json('applied')[0]['op']);
    }
}
