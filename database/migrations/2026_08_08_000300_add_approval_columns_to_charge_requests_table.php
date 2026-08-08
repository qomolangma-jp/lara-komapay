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
        Schema::table('charge_requests', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('description')->constrained('users')->nullOnDelete();
            $table->string('approved_by_name')->nullable()->after('approved_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by_name');

            $table->index(['status', 'approved_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charge_requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'approved_by']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approved_by_name', 'approved_at']);
        });
    }
};
