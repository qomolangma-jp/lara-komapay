<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_create_product_rejects_negative_price_and_stock()
    {
        $payload = [
            'name' => 'テスト商品',
            'price' => -10,
            'stock' => -5,
            'category' => 'テスト',
        ];

        $response = $this->postJson('/api/master/products', $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
        $this->assertArrayHasKey('price', $response->json('errors'));
        $this->assertArrayHasKey('stock', $response->json('errors'));
    }

    public function test_master_create_product_accepts_zero_price_and_stock()
    {
        $payload = [
            'name' => 'テスト商品ゼロ',
            'price' => 0,
            'stock' => 0,
            'category' => 'テスト',
        ];

        $response = $this->postJson('/api/master/products', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'テスト商品ゼロ');
    }
}
