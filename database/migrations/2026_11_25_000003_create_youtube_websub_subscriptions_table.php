<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('youtube_websub_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('channel_id')->index();
            $table->string('callback_token', 64);
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('lease_seconds')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('last_notification_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youtube_websub_subscriptions');
    }
};
