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
        $driver = Schema::getConnection()->getDriverName();

        // 1. Add v3 self-service ownership columns to officers table (PRD 7K)
        Schema::table('officers', function (Blueprint $table) use ($driver) {
            if ($driver === 'sqlite') {
                $table->string('callsign_normalized', 50)
                    ->virtualAs("UPPER(TRIM(callsign))")
                    ->nullable()
                    ->after('callsign');
            } else {
                $table->string('callsign_normalized', 50)
                    ->storedAs("UPPER(TRIM(callsign))")
                    ->nullable()
                    ->after('callsign');
            }

            $table->timestamp('owner_updated_at')->nullable()->after('updated_at');
            $table->timestamp('callsign_changed_at')->nullable()->after('owner_updated_at');
            $table->boolean('avatar_is_custom')->default(false)->after('callsign_changed_at');

            $table->index('callsign_normalized', 'idx_officers_callsign_norm');
        });

        // 2. Create officer_change_requests table for rank & agency changes (PRD 7J)
        Schema::create('officer_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->constrained('officers')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('field', 40); // 'rank_id' | 'agency_id'
            $table->string('old_value', 150)->nullable();
            $table->string('new_value', 150);
            $table->string('reason', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');

            // Unique pending lock: managed on model save (CONCAT(officer_id, ':', field) when pending, null otherwise)
            // Avoiding MySQL 1215 foreign key cascade restriction on generated columns
            $table->string('pending_lock', 60)->nullable()->unique('uq_one_pending_per_field');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_change_req_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officer_change_requests');

        Schema::table('officers', function (Blueprint $table) {
            $table->dropIndex('idx_officers_callsign_norm');
            $table->dropColumn([
                'callsign_normalized',
                'owner_updated_at',
                'callsign_changed_at',
                'avatar_is_custom',
            ]);
        });
    }
};
