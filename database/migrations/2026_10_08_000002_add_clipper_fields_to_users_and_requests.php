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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'clipper_tier')) {
                $table->string('clipper_tier', 20)->default('free')->after('role');
            }
        });

        Schema::table('clipper_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('clipper_requests', 'request_type')) {
                $table->string('request_type', 30)->default('officer')->after('user_id');
            }
            if (!Schema::hasColumn('clipper_requests', 'channel_url')) {
                $table->string('channel_url', 255)->nullable()->after('request_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'clipper_tier')) {
                $table->dropColumn('clipper_tier');
            }
        });

        Schema::table('clipper_requests', function (Blueprint $table) {
            if (Schema::hasColumn('clipper_requests', 'request_type')) {
                $table->dropColumn('request_type');
            }
            if (Schema::hasColumn('clipper_requests', 'channel_url')) {
                $table->dropColumn('channel_url');
            }
        });
    }
};
