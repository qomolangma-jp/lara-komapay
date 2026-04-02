<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('news')) {
            return;
        }

        if (!Schema::hasColumn('news', 'image_url')) {
            Schema::table('news', function (Blueprint $table) {
                $table->string('image_url', 500)->nullable()->after('content');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('news')) {
            return;
        }

        if (Schema::hasColumn('news', 'image_url')) {
            Schema::table('news', function (Blueprint $table) {
                $table->dropColumn('image_url');
            });
        }
    }
};
