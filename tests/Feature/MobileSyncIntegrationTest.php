<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_out_of_order_create_then_update_sequence()
    {
        $register = $this->postJson('/api/mobile/register', [
            'phone' => '256700000010',
            'name' => 'OOO User',
            'pin' => '1010'
        ]);

        $token = $register->json('token');

        $client_id = 'aaa11111-aaaa-1111-aaaa-111111111111';

        // Send an update before create (out-of-order) -> should produce no_mapping conflict
        $update = [[
            'client_id' => $client_id,
            'op' => 'update',
            'payload' => [
                'amount_minor' => 1500,
                'notes' => 'Client pre-update',
                'client_updated_at' => now()->toDateTimeString()
            ]
        ]];

        $resp1 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev-ooo', 'records' => $update]);

        $resp1->assertStatus(200);
        $this->assertNotEmpty($resp1->json('conflicts'));
        $this->assertEquals('no_mapping', $resp1->json('conflicts')[0]['reason']);

        // Now send create -> should apply
        $create = [[
            'client_id' => $client_id,
            'op' => 'create',
            'payload' => [
                'type' => 'income',
                'amount_minor' => 1500,
                'date' => now()->toDateString(),
                'notes' => 'Created after pre-update'
            ]
        ]];

        $resp2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev-ooo', 'records' => $create]);

        $resp2->assertStatus(200);
        $this->assertEmpty($resp2->json('conflicts'));
        $serverId = $resp2->json('applied')[0]['server_id'];

        // Now send an update with client_updated_at > server updated -> should apply
        $later = now()->addMinutes(1)->toDateTimeString();
        $update2 = [[
            'client_id' => $client_id,
            'op' => 'update',
            'payload' => [
                'amount_minor' => 2000,
                'notes' => 'Client post-create update',
                'client_updated_at' => $later
            ]
        ]];

        $resp3 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev-ooo', 'records' => $update2]);

        $resp3->assertStatus(200);
        $this->assertEmpty($resp3->json('conflicts'));

        // verify server record changed
        $server = \App\Models\Banking\Transaction::find($serverId);
        $this->assertEquals(2000, $server->amount);
    }

    public function test_concurrent_device_conflict_resolution()
    {
        $register = $this->postJson('/api/mobile/register', [
            'phone' => '256700000011',
            'name' => 'Concurrent User',
            'pin' => '1111'
        ]);

        $token = $register->json('token');

        $client_id = 'bbb22222-bbbb-2222-bbbb-222222222222';

        // create record
        $create = [[
            'client_id' => $client_id,
            'op' => 'create',
            'payload' => [
                'type' => 'income',
                'amount_minor' => 3000,
                'date' => now()->toDateString(),
                'notes' => 'Initial'
            ]
        ]];

        $cresp = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev-A', 'records' => $create]);

        $serverId = $cresp->json('applied')[0]['server_id'];

        // Device B updates with newer timestamp
        $newer = now()->addMinutes(2)->toDateTimeString();
        $updB = [[
            'client_id' => $client_id,
            'op' => 'update',
            'payload' => [
                'amount_minor' => 3500,
                'notes' => 'Device B edit',
                'client_updated_at' => $newer
            ]
        ]];

        $respB = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev-B', 'records' => $updB]);

        $respB->assertStatus(200);
        $this->assertEmpty($respB->json('conflicts'));

        // Device A updates with older timestamp -> should conflict
        $older = now()->toDateTimeString();
        $updA = [[
            'client_id' => $client_id,
            'op' => 'update',
            'payload' => [
                'amount_minor' => 3200,
                'notes' => 'Device A older edit',
                'client_updated_at' => $older
            ]
        ]];

        $respA = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev-A', 'records' => $updA]);

        $respA->assertStatus(200);
        $this->assertNotEmpty($respA->json('conflicts'));
    }

    public function test_update_after_delete_conflict()
    {
        $register = $this->postJson('/api/mobile/register', [
            'phone' => '256700000012',
            'name' => 'Delete Then Update',
            'pin' => '1212'
        ]);

        $token = $register->json('token');

        $client_id = 'ccc33333-cccc-3333-cccc-333333333333';

        // create
        $create = [[
            'client_id' => $client_id,
            'op' => 'create',
            'payload' => [
                'type' => 'expense',
                'amount_minor' => 4000,
                'date' => now()->toDateString(),
                'notes' => 'Will be deleted'
            ]
        ]];

        $cres = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev-del', 'records' => $create]);

        $serverId = $cres->json('applied')[0]['server_id'];

        // delete
        $del = [[
            'client_id' => $client_id,
            'op' => 'delete',
            'payload' => []
        ]];

        $dres = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev-del', 'records' => $del]);

        $dres->assertStatus(200);
        $this->assertEquals('delete', $dres->json('applied')[0]['op']);

        // attempt an update after delete -> should conflict (missing_server_record or no_mapping)
        $upd = [[
            'client_id' => $client_id,
            'op' => 'update',
            'payload' => [
                'amount_minor' => 4500,
                'notes' => 'Late update',
                'client_updated_at' => now()->toDateTimeString()
            ]
        ]];

        $ures = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/sync', ['device_id' => 'dev-del', 'records' => $upd]);

        $ures->assertStatus(200);
        $this->assertNotEmpty($ures->json('conflicts'));
    }

}
