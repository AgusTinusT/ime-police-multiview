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
        Schema::table('officers', function (Blueprint $table) {
            $table->foreignId('agency_id')->nullable()->after('badge_number')->constrained('agencies')->onDelete('set null');
            $table->foreignId('rank_id')->nullable()->after('agency_id')->constrained('ranks')->onDelete('set null');
            $table->foreignId('division_id')->nullable()->after('rank_id')->constrained('divisions')->onDelete('set null');
            $table->enum('duty_status', ['10-8 (On-Duty)', '10-7 (Off-Duty)', '10-6 (Busy)', 'Suspended'])->default('10-8 (On-Duty)')->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropForeign(['rank_id']);
            $table->dropForeign(['division_id']);
            $table->dropColumn(['agency_id', 'rank_id', 'division_id', 'duty_status']);
        });
    }
};
