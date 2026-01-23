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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 150)->unique();
            $table->string('password')->nullable();
            $table->string('student_id', 50)->nullable()->unique();
            $table->boolean('is_admin')->default(false);
            $table->string('status', 50)->default('student');
            $table->string('name_2nd', 50)->nullable();
            $table->string('name_1st', 50)->nullable();
            $table->string('line_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
