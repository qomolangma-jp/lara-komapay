<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_order_status_update_creates_audit_log()
    {
        $admin = User::create([
            'username' => 'admin-audit-order',
            'password' => 'secret',
            'status' => 'admin',
            'is_admin' => true,
        ]);

        $customer = User::create([
            'username' => 'student-audit-order',
            'password' => 'secret',
            'status' => 'student',
            'is_admin' => false,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'total_price' => 1000,
            'status' => Order::STATUS_COOKING,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/orders/{$order->id}/status", [
            'status' => Order::STATUS_COMPLETED,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.status.updated.admin',
            'target_type' => 'order',
            'target_id' => $order->id,
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_seller_order_status_update_creates_audit_log()
    {
        $seller = User::create([
            'username' => 'seller-audit-order',
            'password' => 'secret',
            'status' => 'seller',
            'is_admin' => false,
        ]);

        $customer = User::create([
            'username' => 'student-audit-seller-order',
            'password' => 'secret',
            'status' => 'student',
            'is_admin' => false,
        ]);

        $product = Product::create([
            'name' => '監査対象商品',
            'price' => 500,
            'stock' => 10,
            'category' => '定食',
            'seller_id' => $seller->id,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'total_price' => 500,
            'status' => Order::STATUS_COOKING,
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($seller);

        $response = $this->putJson("/api/seller/orders/{$order->id}/status", [
            'status' => Order::STATUS_COMPLETED,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.status.updated.seller',
            'target_type' => 'order',
            'target_id' => $order->id,
            'actor_user_id' => $seller->id,
        ]);
    }

    public function test_seller_stock_update_creates_audit_log()
    {
        $seller = User::create([
            'username' => 'seller-audit-stock',
            'password' => 'secret',
            'status' => 'seller',
            'is_admin' => false,
        ]);

        $product = Product::create([
            'name' => '在庫監査商品',
            'price' => 600,
            'stock' => 8,
            'category' => '麺',
            'seller_id' => $seller->id,
        ]);

        Sanctum::actingAs($seller);

        $response = $this->postJson("/api/products/{$product->id}/stock", [
            'stock' => 15,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product.stock.updated',
            'target_type' => 'product',
            'target_id' => $product->id,
            'actor_user_id' => $seller->id,
        ]);
    }
}
