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
        Schema::create('tac_clips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('officer_id')->nullable()->constrained('officers')->onDelete('set null');
            $table->string('video_id', 11);
            $table->string('title');
            $table->integer('start_seconds')->default(0);
            $table->integer('end_seconds')->default(60);
            $table->string('officer_name')->nullable();
            $table->string('officer_handle')->nullable();
            $table->string('creator_name')->nullable();
            $table->integer('likes_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tac_clips');
    }
};
