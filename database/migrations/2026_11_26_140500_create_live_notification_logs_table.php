<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->constrained('officers')->cascadeOnDelete();
            $table->string('video_id', 32)->unique();
            $table->string('title')->nullable();
            $table->string('detection_source', 32)->default('sync')->index();
            $table->timestamp('youtube_started_at')->nullable()->index();
            $table->timestamp('detected_at')->index();
            $table->timestamp('notified_at')->nullable();
            $table->integer('detection_delay_seconds')->nullable();
            $table->integer('delivery_delay_seconds')->nullable();
            $table->integer('total_delay_seconds')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_notification_logs');
    }
};
