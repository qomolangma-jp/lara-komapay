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
        if (!Schema::hasColumn('products', 'additional_image_urls')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('additional_image_urls')->nullable()->after('image_url');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'additional_image_urls')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('additional_image_urls');
            });
        }
    }
};
