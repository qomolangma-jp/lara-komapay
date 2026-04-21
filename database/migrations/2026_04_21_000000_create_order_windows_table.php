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
        Schema::create('order_windows', function (Blueprint $table) {
            $table->id();
            $table->date('target_date')->unique();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_windows');
    }
};
