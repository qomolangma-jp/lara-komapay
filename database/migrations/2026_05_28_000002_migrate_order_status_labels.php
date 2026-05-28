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
        // データの変換のみ（安全）
        DB::table('orders')->where('status', '完了')->update(['status' => '調理済']);
        DB::table('orders')->where('status', '受渡済')->update(['status' => '受取済']);
        DB::table('orders')->where('status', 'キャンセル')->update(['status' => '停止']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('orders')->where('status', '調理済')->update(['status' => '完了']);
        DB::table('orders')->where('status', '受取済')->update(['status' => '受渡済']);
        DB::table('orders')->where('status', '停止')->update(['status' => 'キャンセル']);
    }
};
