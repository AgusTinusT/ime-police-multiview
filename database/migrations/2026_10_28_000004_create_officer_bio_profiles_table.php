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
        Schema::create('officer_bio_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->unique()->constrained('officers')->cascadeOnDelete();
            $table->string('slug', 40)->unique();
            $table->string('display_title', 120)->nullable();
            $table->string('bio_tagline', 255)->nullable();
            $table->string('banner_path', 255)->nullable();
            $table->string('theme_preset', 50)->default('default_glass');
            $table->string('button_style', 30)->default('glass_tactical');
            $table->char('accent_color', 7)->default('#f59e0b');
            $table->boolean('show_live_stream_banner')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_suspended')->default(false);
            $table->string('suspension_reason', 255)->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officer_bio_profiles');
    }
};
