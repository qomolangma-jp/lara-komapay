<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortOrderToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('products', 'sort_order')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('products', 'sort_order')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
}
