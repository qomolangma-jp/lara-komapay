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
        Schema::create('class_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 50)->unique();
            $table->string('class_code', 2);
            $table->unsignedSmallInteger('student_number');
            $table->string('student_name', 100);
            $table->timestamps();

            $table->index(['class_code', 'student_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_profiles');
    }
};
