<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->enum('slide_type', ['feature', 'event', 'cta', 'live_spotlight'])->default('feature');
            $table->string('title', 80)->nullable();
            $table->string('subtitle', 200)->nullable();
            $table->string('badge_label', 24)->nullable();
            $table->enum('accent', [
                'primary',
                'info',
                'success',
                'warning',
                'danger',
                'dept-lspd',
                'dept-bcso',
                'dept-sasp',
                'dept-sapr',
            ])->default('primary');
            $table->string('image_desktop_path')->nullable();
            $table->string('image_mobile_path')->nullable();
            $table->string('image_alt', 150)->nullable();
            $table->string('cta_primary_label', 30)->nullable();
            $table->string('cta_primary_url', 500)->nullable();
            $table->string('cta_secondary_label', 30)->nullable();
            $table->string('cta_secondary_url', 500)->nullable();
            $table->enum('audience', ['all', 'guest', 'member', 'officer'])->default('all');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('hero_slide_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_slide_id')->constrained('hero_slides')->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();

            $table->unique(['hero_slide_id', 'stat_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slide_stats');
        Schema::dropIfExists('hero_slides');
    }
};

