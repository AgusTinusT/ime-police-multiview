<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('officer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('officer_id')->constrained()->cascadeOnDelete();
            $table->boolean('notify_in_app')->default(true);
            $table->boolean('notify_web_push')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'officer_id']);
            $table->index('officer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officer_subscriptions');
    }
};
