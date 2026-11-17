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
            $table->unsignedInteger('subscriber_count')->nullable()->after('avatar_url');
            $table->string('subscriber_count_text', 50)->nullable()->after('subscriber_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->dropColumn(['subscriber_count', 'subscriber_count_text']);
        });
    }
};
