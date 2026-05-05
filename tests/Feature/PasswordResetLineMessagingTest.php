<?php

namespace Tests\Feature;

use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PasswordResetLineMessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'services.line_messaging.channel_access_token' => 'test-channel-token',
            'services.line_messaging.channel_secret' => 'test-channel-secret',
        ]);
    }

    public function test_send_code_sends_line_message_and_stores_hashed_code(): void
    {
        $user = User::create([
            'username' => 'student-a',
            'student_id' => 'S10001',
            'line_id' => 'U-old-line-id',
            'line_user_id' => 'U-test-line-id',
            'password' => Hash::make('old-password'),
            'status' => 'student',
            'is_admin' => false,
        ]);

        Http::fake();

        $response = $this->postJson('/api/password-reset/send-code', [
            'student_id' => $user->student_id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        Http::assertSentCount(1);
        $sent = Http::recorded()[0][0];
        $payload = json_decode($sent->body(), true);
        $this->assertSame('U-test-line-id', $payload['to']);
        $this->assertStringContainsString('【コマペイ】本人確認', $payload['messages'][0]['text']);

        $record = PasswordResetCode::where('student_id', $user->student_id)->first();
        $this->assertNotNull($record);
        $this->assertTrue($record->expires_at->greaterThan(now()));
    }

    public function test_send_code_returns_line_not_linked_when_line_user_id_is_missing(): void
    {
        $user = User::create([
            'username' => 'student-b',
            'student_id' => 'S10002',
            'password' => Hash::make('old-password'),
            'status' => 'student',
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/password-reset/send-code', [
            'student_id' => $user->student_id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'LINE_NOT_LINKED');
    }

    public function test_send_code_is_rate_limited_per_student(): void
    {
        User::create([
            'username' => 'student-c',
            'student_id' => 'S10003',
            'line_user_id' => 'U-test-line-id-2',
            'password' => Hash::make('old-password'),
            'status' => 'student',
            'is_admin' => false,
        ]);

        Http::fake();

        $first = $this->postJson('/api/password-reset/send-code', [
            'student_id' => 'S10003',
        ]);
        $first->assertOk();

        $second = $this->postJson('/api/password-reset/send-code', [
            'student_id' => 'S10003',
        ]);
        $second->assertStatus(429);
        $second->assertJsonPath('code', 'TOO_MANY_REQUESTS');
    }

    public function test_verify_and_update_replaces_password_when_code_matches(): void
    {
        $user = User::create([
            'username' => 'student-d',
            'student_id' => 'S10004',
            'line_user_id' => 'U-test-line-id-4',
            'password' => Hash::make('old-password'),
            'status' => 'student',
            'is_admin' => false,
        ]);

        PasswordResetCode::create([
            'student_id' => $user->student_id,
            'user_id' => $user->id,
            'line_user_id' => $user->line_user_id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'sent_at' => now(),
            'attempts' => 0,
        ]);

        $response = $this->postJson('/api/password-reset/verify-and-update', [
            'student_id' => $user->student_id,
            'code' => '123456',
            'new_password' => 'new-secret',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $user->refresh();
        $this->assertTrue(Hash::check('new-secret', $user->password));

        $record = PasswordResetCode::where('student_id', $user->student_id)->first();
        $this->assertNotNull($record->used_at);
    }
}