<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('line_user_id')->nullable()->unique()->after('line_id');
        });

        DB::statement('UPDATE users SET line_user_id = line_id WHERE line_id IS NOT NULL AND line_id <> ""');

        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 50)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('line_user_id')->nullable();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['expires_at']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['line_user_id']);
            $table->dropColumn('line_user_id');
        });
    }
};