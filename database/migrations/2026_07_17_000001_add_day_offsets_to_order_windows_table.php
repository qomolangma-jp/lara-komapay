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
        Schema::table('order_windows', function (Blueprint $table) {
            $table->tinyInteger('start_day_offset')->default(0)->after('target_date');
            $table->tinyInteger('end_day_offset')->default(0)->after('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_windows', function (Blueprint $table) {
            $table->dropColumn(['start_day_offset', 'end_day_offset']);
        });
    }
};
