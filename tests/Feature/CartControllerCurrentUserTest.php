<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartControllerCurrentUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_cart_endpoint_returns_only_authenticated_users_cart_items(): void
    {
        $currentUser = User::create([
            'username' => 'current-user',
            'status' => 'student',
            'is_admin' => false,
        ]);

        $otherUser = User::create([
            'username' => 'other-user',
            'status' => 'student',
            'is_admin' => false,
        ]);

        $productA = Product::create([
            'name' => '唐揚げ定食',
            'price' => 500,
            'stock' => 20,
        ]);

        $productB = Product::create([
            'name' => 'カレーライス',
            'price' => 450,
            'stock' => 20,
        ]);

        $currentCart = CartItem::create([
            'user_id' => $currentUser->id,
            'product_id' => $productA->id,
            'quantity' => 2,
        ]);

        CartItem::create([
            'user_id' => $otherUser->id,
            'product_id' => $productB->id,
            'quantity' => 3,
        ]);

        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/master/cart');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('history_mode', 'current_user_cart_items');
        $response->assertJsonCount(1, 'carts');
        $response->assertJsonPath('carts.0.id', $currentCart->id);
        $response->assertJsonPath('carts.0.user_id', $currentUser->id);
        $this->assertSame('唐揚げ定食', $response->json('carts.0.product.name'));
    }

    public function test_master_cart_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/master/cart');

        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
    }
}