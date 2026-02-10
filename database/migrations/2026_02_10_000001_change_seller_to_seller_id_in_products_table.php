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
        Schema::table('products', function (Blueprint $table) {
            // sellerカラムを削除
            $table->dropColumn('seller');
            
            // seller_idカラムを追加（usersテーブルへの外部キー）
            $table->foreignId('seller_id')->nullable()->after('category')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropColumn('seller_id');
            
            // sellerカラムを復元
            $table->string('seller', 100)->nullable()->after('category');
        });
    }
};
