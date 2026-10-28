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
            $table->enum('status', ['active', 'banned'])->default('active')->after('role');
            $table->string('banned_reason', 255)->nullable()->after('status');
            $table->timestamp('banned_at')->nullable()->after('banned_reason');
            $table->string('name_skeleton', 150)->nullable()->after('name')->index('idx_users_name_skeleton');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_name_skeleton');
            $table->dropColumn(['status', 'banned_reason', 'banned_at', 'name_skeleton']);
        });
    }
};
