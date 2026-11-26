<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('officer_subscriptions', function (Blueprint $table) {
            $table->boolean('notify_discord_dm')->default(true)->after('notify_web_push');
        });
    }

    public function down(): void
    {
        Schema::table('officer_subscriptions', function (Blueprint $table) {
            $table->dropColumn('notify_discord_dm');
        });
    }
};
