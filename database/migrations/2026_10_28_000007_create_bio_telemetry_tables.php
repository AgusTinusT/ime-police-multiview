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
        Schema::create('bio_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_bio_link_id')->constrained('officer_bio_links')->cascadeOnDelete();
            $table->char('visitor_hash', 64)->nullable();
            $table->string('referer_host', 190)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['officer_bio_link_id', 'created_at'], 'idx_link_created');
        });

        Schema::create('bio_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_bio_profile_id')->constrained('officer_bio_profiles')->cascadeOnDelete();
            $table->foreignId('officer_bio_link_id')->nullable()->constrained('officer_bio_links')->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);

            $table->unique(['officer_bio_profile_id', 'officer_bio_link_id', 'stat_date'], 'uq_stat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bio_daily_stats');
        Schema::dropIfExists('bio_link_clicks');
    }
};
