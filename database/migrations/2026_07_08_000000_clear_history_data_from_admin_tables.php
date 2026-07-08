<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function clearTable(string $table): void
    {
        if (Schema::hasTable($table)) {
            DB::table($table)->delete();
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->clearTable('order_details');
        $this->clearTable('orders');
        $this->clearTable('cart_logs');
        $this->clearTable('cart_items');
        $this->clearTable('news');
        $this->clearTable('audit_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
