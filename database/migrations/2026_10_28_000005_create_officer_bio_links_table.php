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
        Schema::create('officer_bio_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_bio_profile_id')->constrained('officer_bio_profiles')->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('subtitle', 180)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('url_host', 190)->nullable();
            $table->string('icon', 60)->default('external-link');
            $table->enum('link_type', ['donation', 'discord', 'officer_recommendation', 'social', 'custom'])->default('custom');
            $table->foreignId('recommended_officer_id')->nullable()->constrained('officers')->cascadeOnDelete();
            $table->string('badge_label', 30)->nullable();
            $table->boolean('is_highlighted')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('moderation_status', ['approved', 'pending_review', 'flagged', 'banned'])->default('approved');
            $table->string('moderation_reason', 255)->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['officer_bio_profile_id', 'sort_order'], 'idx_profile_order');
            $table->index('url_host', 'idx_host');
            $table->index('moderation_status', 'idx_moderation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officer_bio_links');
    }
};
