<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SellerReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_report_returns_only_own_orders()
    {
        $seller = User::create([
            'username' => 'seller-1',
            'password' => 'secret',
            'status' => 'seller',
            'shop_name' => '販売者A',
            'is_admin' => false,
        ]);

        $otherSeller = User::create([
            'username' => 'seller-2',
            'password' => 'secret',
            'status' => 'seller',
            'shop_name' => '販売者B',
            'is_admin' => false,
        ]);

        $customer = User::create([
            'username' => 'student-1',
            'password' => 'secret',
            'status' => 'student',
            'is_admin' => false,
        ]);

        $sellerProduct = Product::create([
            'name' => '売上対象商品',
            'price' => 500,
            'stock' => 10,
            'category' => '定食',
            'seller_id' => $seller->id,
        ]);

        $otherProduct = Product::create([
            'name' => '別販売者商品',
            'price' => 700,
            'stock' => 10,
            'category' => '麺',
            'seller_id' => $otherSeller->id,
        ]);

        $sellerOrder = Order::create([
            'user_id' => $customer->id,
            'total_price' => 1000,
            'status' => Order::STATUS_COMPLETED,
        ]);
        OrderDetail::create([
            'order_id' => $sellerOrder->id,
            'product_id' => $sellerProduct->id,
            'quantity' => 2,
        ]);

        $otherOrder = Order::create([
            'user_id' => $customer->id,
            'total_price' => 700,
            'status' => Order::STATUS_COMPLETED,
        ]);
        OrderDetail::create([
            'order_id' => $otherOrder->id,
            'product_id' => $otherProduct->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($seller);

        $response = $this->getJson('/api/seller/reports');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.summary.total_orders', 1);
        $response->assertJsonPath('data.summary.total_sales', 1000);
        $response->assertJsonPath('data.orders.0.order_id', $sellerOrder->id);
        $response->assertJsonPath('data.orders.0.item_summary', '売上対象商品 ×2');
    }

    public function test_seller_report_export_returns_csv()
    {
        $seller = User::create([
            'username' => 'seller-export',
            'password' => 'secret',
            'status' => 'seller',
            'shop_name' => '販売者CSV',
            'is_admin' => false,
        ]);

        $customer = User::create([
            'username' => 'student-export',
            'password' => 'secret',
            'status' => 'student',
            'is_admin' => false,
        ]);

        $product = Product::create([
            'name' => 'CSV商品',
            'price' => 800,
            'stock' => 10,
            'category' => 'カレー',
            'seller_id' => $seller->id,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'total_price' => 1600,
            'status' => Order::STATUS_COOKING,
        ]);
        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        Sanctum::actingAs($seller);

        $response = $this->get('/api/seller/reports/export');

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('注文ID', $content);
        $this->assertStringContainsString('CSV商品', $content);
    }
}
