<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_login_works_without_csrf_token_for_api_clients()
    {
        User::create([
            'username' => 'student-login',
            'password' => Hash::make('secret1234'),
            'status' => 'student',
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'student-login',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['user', 'token']);
        $this->assertNotEquals(419, $response->status());
    }

    public function test_register_validation_rejects_missing_required_fields()
    {
        $response = $this->postJson('/api/auth/register', [
            'name_2nd' => '',
            'name_1st' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
        $this->assertArrayHasKey('line_id', $response->json('errors'));
        $this->assertArrayHasKey('name_2nd', $response->json('errors'));
        $this->assertArrayHasKey('name_1st', $response->json('errors'));
    }

    public function test_admin_only_auth_users_endpoint_requires_authentication()
    {
        $response = $this->getJson('/api/auth/users');

        $response->assertStatus(401);
    }

    public function test_admin_only_auth_users_endpoint_forbids_non_admin_user()
    {
        $seller = User::create([
            'username' => 'seller-user',
            'password' => Hash::make('secret1234'),
            'status' => 'seller',
            'is_admin' => false,
        ]);

        Sanctum::actingAs($seller);

        $response = $this->getJson('/api/auth/users');

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    public function test_admin_only_auth_users_endpoint_allows_admin_user()
    {
        $admin = User::create([
            'username' => 'admin-user',
            'password' => Hash::make('secret1234'),
            'status' => 'admin',
            'is_admin' => true,
        ]);

        User::create([
            'username' => 'student-target',
            'password' => Hash::make('secret1234'),
            'status' => 'student',
            'is_admin' => false,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/auth/users');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonIsArray('data');
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_admin_create_user_validation_rejects_invalid_username_format()
    {
        $admin = User::create([
            'username' => 'admin-create',
            'password' => Hash::make('secret1234'),
            'status' => 'admin',
            'is_admin' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/auth/users', [
            'username' => '全角ユーザー名',
            'name_2nd' => '山田',
            'name_1st' => '太郎',
            'password' => '1234',
            'status' => 'student',
            'is_admin' => false,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
        $this->assertArrayHasKey('username', $response->json('errors'));
    }
}
