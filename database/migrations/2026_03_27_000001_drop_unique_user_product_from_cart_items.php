<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasUserForeign = $this->indexExists('cart_items', 'cart_items_user_id_foreign');
        $hasProductForeign = $this->indexExists('cart_items', 'cart_items_product_id_foreign');
        $hasUniqueIndex = $this->indexExists('cart_items', 'cart_items_user_id_product_id_unique');

        Schema::table('cart_items', function (Blueprint $table) use ($hasUserForeign, $hasProductForeign, $hasUniqueIndex) {
            if ($hasUserForeign) {
                $table->dropForeign(['user_id']);
            }
            if ($hasProductForeign) {
                $table->dropForeign(['product_id']);
            }
            if ($hasUniqueIndex) {
                $table->dropUnique('cart_items_user_id_product_id_unique');
            }

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['product_id']);
            $table->unique(['user_id', 'product_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $results = DB::select('SHOW INDEX FROM `' . $tableName . '` WHERE Key_name = ?', [$indexName]);
        return count($results) > 0;
    }
};
