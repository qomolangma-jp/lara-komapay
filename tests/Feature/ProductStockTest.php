<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_hasStock_and_decrementStock_basic()
    {
        $product = Product::create([
            'name' => '在庫テスト商品',
            'price' => 100,
            'stock' => 5,
            'category' => 'テスト',
            'seller_id' => 1,
        ]);

        $this->assertTrue($product->hasStock(3));

        $product->decrementStock(3);
        $product->refresh();
        $this->assertEquals(2, $product->stock);

        // 追加減算が期待通り在庫を減らす
        $product->decrementStock(2);
        $product->refresh();
        $this->assertEquals(0, $product->stock);
    }

    public function test_simulated_race_condition_allows_oversell()
    {
        // 初期在庫1を用意し、2つの並列処理を模擬して両方が在庫チェックを通るケースを再現
        $product = Product::create([
            'name' => '競合テスト商品',
            'price' => 200,
            'stock' => 1,
            'category' => 'テスト',
            'seller_id' => 1,
        ]);

        // 2つのプロセスがほぼ同時に在庫を確認
        $pA = Product::find($product->id);
        $pB = Product::find($product->id);

        $this->assertTrue($pA->hasStock(1));
        $this->assertTrue($pB->hasStock(1));

        // A が減算を実行
        $okA = $pA->decrementStock(1);
        // B も減算を試みるが、原子的な減算は失敗するはず
        $okB = $pB->decrementStock(1);

        $final = Product::find($product->id);

        $this->assertTrue($okA);
        $this->assertFalse($okB);
        // 最終在庫は 0 で、負にはならない
        $this->assertEquals(0, $final->stock);
    }
}
