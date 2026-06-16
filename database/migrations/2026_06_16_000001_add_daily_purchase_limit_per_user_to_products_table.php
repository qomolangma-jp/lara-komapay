<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'daily_purchase_limit_per_user')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('daily_purchase_limit_per_user')->nullable()->after('stock');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'daily_purchase_limit_per_user')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('daily_purchase_limit_per_user');
            });
        }
    }
};
