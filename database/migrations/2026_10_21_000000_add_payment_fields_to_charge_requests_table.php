<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charge_requests', function (Blueprint $table) {
            $table->string('method', 30)->default('cash');
            $table->unsignedBigInteger('payment_fee')->default(0);
            $table->unsignedBigInteger('payment_total')->default(0);
            $table->string('paypay_payment_id')->nullable()->unique();
            $table->string('paypay_transaction_id')->nullable()->unique();
            $table->timestamp('paypay_paid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('charge_requests', function (Blueprint $table) {
            $table->dropUnique(['paypay_payment_id']);
            $table->dropUnique(['paypay_transaction_id']);
            $table->dropColumn([
                'method',
                'payment_fee',
                'payment_total',
                'paypay_payment_id',
                'paypay_transaction_id',
                'paypay_paid_at',
            ]);
        });
    }
};
