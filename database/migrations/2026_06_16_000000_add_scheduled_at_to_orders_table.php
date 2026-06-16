<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'scheduled_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dateTime('scheduled_at')->nullable()->after('paid_at')->index();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'scheduled_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('scheduled_at');
            });
        }
    }
};
