<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'size_options')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('size_options')->nullable()->after('allergens');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'size_options')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('size_options');
            });
        }
    }
};