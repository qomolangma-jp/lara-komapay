<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 50)->default('cash')->after('status');
            $table->string('payment_status', 50)->default('pending')->after('payment_method');
            $table->string('paypay_payment_id')->nullable()->after('payment_status');
            $table->text('paypay_redirect_url')->nullable()->after('paypay_payment_id');
            $table->timestamp('paid_at')->nullable()->after('paypay_redirect_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'paypay_payment_id',
                'paypay_redirect_url',
                'paid_at',
            ]);
        });
    }
};
