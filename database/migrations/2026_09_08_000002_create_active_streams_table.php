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
        Schema::create('active_streams', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id', 64)->index();
            $table->string('video_id', 32)->index();
            $table->string('title', 255)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('status', 20)->default('LIVE');
            $table->string('incident_code', 100)->default('10-8 Routine Patrol');
            $table->integer('viewers_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('channel_id')
                ->references('channel_id')
                ->on('officers')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_streams');
    }
};
