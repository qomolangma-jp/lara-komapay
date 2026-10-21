<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->string('type', 20)->nullable()->after('order_id');
            $table->string('method', 20)->nullable()->after('type');
            $table->unsignedBigInteger('charge_request_id')->nullable()->after('method');
            $table->index(['user_id', 'id']);
        });

        DB::table('point_transactions')->where('transaction_type', 'charge_paypay')
            ->update(['type' => 'charge', 'method' => 'paypay']);
        DB::table('point_transactions')->where('transaction_type', 'charge_approval')
            ->update(['type' => 'charge']);
        DB::table('point_transactions')->where('transaction_type', 'spend')
            ->update(['type' => 'purchase']);
    }

    public function down(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'id']);
            $table->dropColumn(['type', 'method', 'charge_request_id']);
        });
    }
};
