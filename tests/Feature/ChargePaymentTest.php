<?php

namespace Tests\Feature;

use App\Models\ChargeRequest;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PayPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class ChargePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_transfer_application_stays_pending_without_adding_points(): void
    {
        $user = $this->createUser('charge-bank-user');
        Sanctum::actingAs($user);
        config()->set('services.charge.transfer_instructions', [
            'bank_name' => '銀行',
            'branch_name' => '本店',
            'account_type' => '普通',
            'account_number' => 'configured-number',
            'account_holder' => '設定名義',
            'deadline' => '2026-10-31',
        ]);

        $response = $this->postJson('/api/charge/apply', [
            'amount' => 1000,
            'method' => 'bank_transfer',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.method', 'bank_transfer')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.transfer_instructions.account_number', 'configured-number');
        $this->assertSame(0, (int) $user->fresh()->points_balance);
        $this->assertDatabaseHas('charge_requests', [
            'user_id' => $user->id,
            'amount' => 1000,
            'method' => 'bank_transfer',
            'status' => 'pending',
        ]);
    }

    public function test_cash_application_is_compatible_and_does_not_return_transfer_instructions(): void
    {
        $user = $this->createUser('charge-cash-user');
        Sanctum::actingAs($user);

        $this->postJson('/api/charge/apply', ['amount' => 100])
            ->assertCreated()
            ->assertJsonPath('data.amount', 100);

        $response = $this->postJson('/api/charge/apply', ['amount' => 49999]);

        $response->assertCreated()
            ->assertJsonPath('data.method', 'cash')
            ->assertJsonPath('data.amount', 49999)
            ->assertJsonMissingPath('data.transfer_instructions');
        $this->assertSame(0, (int) $user->fresh()->points_balance);
    }

    public function test_charge_application_requires_authentication_and_valid_amount_and_method(): void
    {
        $this->postJson('/api/charge/apply', ['amount' => 100, 'method' => 'cash'])
            ->assertUnauthorized();
        $this->postJson('/api/charge/paypay', [])->assertUnauthorized();
        $this->postJson('/api/charge/paypay/confirm', [])->assertUnauthorized();

        Sanctum::actingAs($this->createUser('charge-validation-user'));

        $this->postJson('/api/charge/apply', ['amount' => 99, 'method' => 'cash'])->assertUnprocessable();
        $this->postJson('/api/charge/apply', ['amount' => 50000, 'method' => 'cash'])->assertUnprocessable();
        $this->postJson('/api/charge/apply', ['amount' => 100.5, 'method' => 'cash'])->assertUnprocessable();
        $this->postJson('/api/charge/apply', ['amount' => 100, 'method' => 'crypto'])->assertUnprocessable();
    }

    public function test_only_master_can_approve_a_pending_charge_once(): void
    {
        $user = $this->createUser('charge-approval-user');
        $request = ChargeRequest::create([
            'user_id' => $user->id,
            'amount' => 700,
            'method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->createUser('charge-teacher', 'teacher'));
        $this->postJson("/api/master/points/requests/{$request->id}/approve")->assertForbidden();
        $this->assertSame(0, (int) $user->fresh()->points_balance);

        Sanctum::actingAs($this->createUser('charge-master', 'master'));
        $this->postJson("/api/master/points/requests/{$request->id}/approve")->assertOk();
        $this->postJson("/api/master/points/requests/{$request->id}/approve")->assertOk();

        $this->assertSame(700, (int) $user->fresh()->points_balance);
        $this->assertSame(1, PointTransaction::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('charge_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'method' => 'bank_transfer',
        ]);
    }

    public function test_existing_cash_request_can_still_be_approved_from_master_screen(): void
    {
        $user = $this->createUser('charge-cash-approval-user');
        $request = ChargeRequest::create([
            'user_id' => $user->id,
            'amount' => 500,
            'method' => 'cash',
            'status' => 'pending',
        ]);
        Sanctum::actingAs($this->createUser('charge-cash-master', 'master'));

        $this->postJson("/api/master/points/requests/{$request->id}/approve")->assertOk();

        $this->assertSame(500, (int) $user->fresh()->points_balance);
        $this->assertDatabaseHas('charge_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);
    }

    public function test_paypay_start_uses_server_fee_and_allowlisted_return_url(): void
    {
        $user = $this->createUser('charge-paypay-start-user');
        Sanctum::actingAs($user);
        config()->set('services.charge.frontend_allowed_origins', ['https://frontend.example']);
        config()->set('services.charge.paypay_fee_rate', 0.05);

        $this->mock(PayPayService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createQrCodePayment')
                ->once()
                ->withArgs(function ($paymentId, $total, $description, $returnUrl): bool {
                    return $total === 1050
                        && $description === 'ポイントチャージ'
                        && str_starts_with($paymentId, 'charge_')
                        && $returnUrl === 'https://frontend.example/charge/paypay/return?merchantPaymentId=' . $paymentId;
                })
                ->andReturnUsing(fn ($paymentId) => [
                    'payment_id' => $paymentId,
                    'payment_url' => 'https://paypay.example/pay',
                ]);
        });

        $response = $this->postJson('/api/charge/paypay', [
            'amount' => 1000,
            'return_url' => 'https://frontend.example/charge/paypay/return',
            'fee' => 1,
            'total' => 1001,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment_url', 'https://paypay.example/pay');
        $this->assertDatabaseHas('charge_requests', [
            'id' => $response->json('data.charge_request_id'),
            'user_id' => $user->id,
            'amount' => 1000,
            'method' => 'paypay',
            'status' => 'pending',
            'payment_fee' => 50,
            'payment_total' => 1050,
        ]);
        $this->assertSame(0, (int) $user->fresh()->points_balance);
    }

    public function test_paypay_start_rejects_unapproved_return_urls(): void
    {
        Sanctum::actingAs($this->createUser('charge-paypay-url-user'));
        config()->set('services.charge.frontend_allowed_origins', ['https://frontend.example']);

        $this->postJson('/api/charge/paypay', [
            'amount' => 100,
            'return_url' => 'https://attacker.example/charge/paypay/return',
        ])->assertUnprocessable();
        $this->postJson('/api/charge/paypay', [
            'amount' => 100,
            'return_url' => 'https://frontend.example/untrusted/redirect',
        ])->assertUnprocessable();
        $this->postJson('/api/charge/paypay', [
            'amount' => 100,
            'return_url' => 'https://frontend.example/charge/paypay/return?next=https://attacker.example',
        ])->assertUnprocessable();
    }

    public function test_paypay_cancellation_and_amount_mismatch_do_not_add_points(): void
    {
        $user = $this->createUser('charge-paypay-reject-user');
        Sanctum::actingAs($user);
        $request = $this->createPayPayRequest($user, 'charge_cancel');

        $this->mock(PayPayService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getPaymentDetails')
                ->once()
                ->with('charge_cancel')
                ->andReturn(['data' => [
                    'merchantPaymentId' => 'charge_cancel',
                    'transactionId' => 'transaction-cancel',
                    'status' => 'CANCELED',
                    'amount' => ['amount' => 1050, 'currency' => 'JPY'],
                ]]);
        });

        $this->postJson('/api/charge/paypay/confirm', [
            'merchantPaymentId' => 'charge_cancel',
        ])->assertUnprocessable();
        $this->assertSame(0, (int) $user->fresh()->points_balance);
        $this->assertSame('pending', $request->fresh()->status);

        $this->mock(PayPayService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getPaymentDetails')
                ->once()
                ->andReturn(['data' => [
                    'merchantPaymentId' => 'charge_cancel',
                    'transactionId' => 'transaction-paid',
                    'status' => 'COMPLETED',
                    'amount' => ['amount' => 1049, 'currency' => 'JPY'],
                ]]);
        });

        $this->postJson('/api/charge/paypay/confirm', [
            'merchantPaymentId' => 'charge_cancel',
        ])->assertUnprocessable();
        $this->assertSame(0, (int) $user->fresh()->points_balance);
    }

    public function test_paypay_confirmation_ignores_forged_browser_status_when_provider_reports_unpaid(): void
    {
        $user = $this->createUser('charge-paypay-forged-user');
        Sanctum::actingAs($user);
        $request = $this->createPayPayRequest($user, 'charge_forged');

        $this->mock(PayPayService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getPaymentDetails')
                ->once()
                ->with('charge_forged')
                ->andReturn(['data' => [
                    'merchantPaymentId' => 'charge_forged',
                    'status' => 'CREATED',
                    'amount' => ['amount' => 1050, 'currency' => 'JPY'],
                ]]);
        });

        $this->postJson('/api/charge/paypay/confirm', [
            'merchantPaymentId' => 'charge_forged',
            'transaction_id' => 'forged-transaction',
            'status' => 'COMPLETED',
        ])->assertUnprocessable();

        $this->assertSame(0, (int) $user->fresh()->points_balance);
        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_paypay_confirmation_requires_merchant_payment_id_only(): void
    {
        Sanctum::actingAs($this->createUser('charge-paypay-required-user'));

        $this->postJson('/api/charge/paypay/confirm', [])->assertUnprocessable();
    }

    public function test_paypay_confirmation_verifies_owner_and_is_idempotent(): void
    {
        $user = $this->createUser('charge-paypay-confirm-user');
        $otherUser = $this->createUser('charge-paypay-other-user');
        Sanctum::actingAs($user);
        $this->createPayPayRequest($otherUser, 'charge_other');
        $request = $this->createPayPayRequest($user, 'charge_owned');

        $this->postJson('/api/charge/paypay/confirm', [
            'merchantPaymentId' => 'charge_other',
        ])->assertNotFound();

        $this->mock(PayPayService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getPaymentDetails')
                ->once()
                ->with('charge_owned')
                ->andReturn(['data' => [
                    'merchantPaymentId' => 'charge_owned',
                    'transactionId' => 'transaction-owned',
                    'status' => 'COMPLETED',
                    'amount' => ['amount' => 1050, 'currency' => 'JPY'],
                ]]);
        });

        $this->postJson('/api/charge/paypay/confirm', [
            'merchantPaymentId' => 'charge_owned',
        ])->assertOk()->assertJsonPath('data.amount', 1000);

        $this->postJson('/api/charge/paypay/confirm', [
            'merchantPaymentId' => 'charge_owned',
        ])->assertStatus(409);

        $this->assertSame(1000, (int) $user->fresh()->points_balance);
        $this->assertSame(0, (int) $otherUser->fresh()->points_balance);
        $this->assertSame(1, PointTransaction::where('user_id', $user->id)->count());
        $this->assertSame('completed', $request->fresh()->status);
        $this->assertSame('transaction-owned', $request->fresh()->paypay_transaction_id);
    }

    public function test_paypay_requests_cannot_be_manually_approved(): void
    {
        $user = $this->createUser('charge-paypay-manual-user');
        $request = $this->createPayPayRequest($user, 'charge_manual');
        Sanctum::actingAs($this->createUser('charge-paypay-manual-master', 'master'));

        $this->postJson("/api/master/points/requests/{$request->id}/approve")->assertUnprocessable();

        $this->assertSame(0, (int) $user->fresh()->points_balance);
        $this->assertSame('pending', $request->fresh()->status);
    }

    private function createUser(string $username, string $status = 'student'): User
    {
        return User::create([
            'username' => $username,
            'password' => 'secret',
            'status' => $status,
            'is_admin' => $status === 'master',
        ]);
    }

    private function createPayPayRequest(User $user, string $merchantPaymentId): ChargeRequest
    {
        return ChargeRequest::create([
            'user_id' => $user->id,
            'amount' => 1000,
            'method' => 'paypay',
            'status' => 'pending',
            'payment_fee' => 50,
            'payment_total' => 1050,
            'paypay_payment_id' => $merchantPaymentId,
        ]);
    }
}
