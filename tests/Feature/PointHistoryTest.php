<?php

namespace Tests\Feature;

use App\Models\ChargeRequest;
use App\Models\User;
use App\Services\PointLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PointHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_requires_authentication(): void
    {
        $this->getJson('/api/points/history')->assertUnauthorized();
    }

    public function test_returns_only_own_history_newest_first_with_signed_amounts(): void
    {
        $user = User::create(['username' => 'hist-a', 'password' => 'secret', 'status' => 'student']);
        $other = User::create(['username' => 'hist-b', 'password' => 'secret', 'status' => 'student']);

        PointLedgerService::apply($user->id, 1000, 'charge', ['method' => 'paypay', 'description' => 'PayPayチャージ']);
        PointLedgerService::apply($user->id, -390, 'purchase', ['description' => '注文 #1']);
        PointLedgerService::apply($other->id, 500, 'grant');

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/points/history')->assertOk();

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertSame('purchase', $data[0]['type']);
        $this->assertSame(-390, $data[0]['amount']);
        $this->assertSame(610, $data[0]['balance_after']);
        $this->assertArrayNotHasKey('method', $data[0]);
        $this->assertSame('charge', $data[1]['type']);
        $this->assertSame('paypay', $data[1]['method']);
        $this->assertSame(1000, $data[1]['amount']);
    }

    public function test_pagination_and_overdraft_rejected(): void
    {
        $user = User::create(['username' => 'hist-c', 'password' => 'secret', 'status' => 'student']);
        foreach (range(1, 3) as $i) {
            PointLedgerService::apply($user->id, 100, 'grant');
        }

        Sanctum::actingAs($user);
        $this->getJson('/api/points/history?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->expectException(\DomainException::class);
        PointLedgerService::apply($user->id, -1000, 'purchase');
    }

    public function test_master_approval_writes_ledger_with_method(): void
    {
        $user = User::create(['username' => 'hist-d', 'password' => 'secret', 'status' => 'student']);
        $request = ChargeRequest::create([
            'user_id' => $user->id, 'amount' => 700, 'method' => 'bank_transfer', 'status' => 'pending',
        ]);
        Sanctum::actingAs(User::create(['username' => 'hist-m', 'password' => 'secret', 'status' => 'master', 'is_admin' => true]));
        $this->postJson("/api/master/points/requests/{$request->id}/approve")->assertOk();

        Sanctum::actingAs($user);
        $this->getJson('/api/points/history')
            ->assertJsonPath('data.0.method', 'bank_transfer')
            ->assertJsonPath('data.0.amount', 700)
            ->assertJsonPath('data.0.balance_after', 700);
    }
}
