<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $dbName = DB::getDatabaseName();

        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', 'cart_items')
            ->where('index_name', 'cart_items_user_id_product_id_unique')
            ->exists();

        if ($exists) {
            DB::statement('ALTER TABLE cart_items DROP INDEX cart_items_user_id_product_id_unique');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $dbName = DB::getDatabaseName();

        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', 'cart_items')
            ->where('index_name', 'cart_items_user_id_product_id_unique')
            ->exists();

        if (!$exists) {
            DB::statement('ALTER TABLE cart_items ADD UNIQUE cart_items_user_id_product_id_unique (user_id, product_id)');
        }
    }
};
