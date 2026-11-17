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
        Schema::create('officer_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('officer_id')->constrained('officers')->cascadeOnDelete();
            $table->enum('verification_method', ['manual_admin', 'youtube_token'])->default('manual_admin');
            $table->char('verification_token_hash', 64)->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedSmallInteger('check_attempts')->default(0);
            $table->string('proof_discord_tag', 100)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired', 'cancelled'])->default('pending');
            
            // Generated columns to enforce maximum 1 pending claim per officer and per user at DB level.
            // CASE (bukan IF) supaya sama-sama jalan di MySQL dan SQLite (test/CI); hasilnya identik.
            $table->unsignedBigInteger('pending_officer_lock')
                ->virtualAs("CASE WHEN status = 'pending' THEN officer_id END")
                ->nullable();
            $table->unique('pending_officer_lock', 'uq_one_pending_per_officer');

            $table->unsignedBigInteger('pending_user_lock')
                ->virtualAs("CASE WHEN status = 'pending' THEN user_id END")
                ->nullable();
            $table->unique('pending_user_lock', 'uq_one_pending_per_user');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_claims_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officer_claims');
    }
};
