<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('discord_id', 32)->nullable()->unique()->after('clipper_tier');
            $table->string('discord_username', 100)->nullable()->after('discord_id');
            $table->string('discord_avatar', 128)->nullable()->after('discord_username');
            $table->string('discord_dm_channel_id', 32)->nullable()->after('discord_avatar');
            $table->timestamp('discord_connected_at')->nullable()->after('discord_dm_channel_id');
            $table->string('discord_dm_failed_reason', 255)->nullable()->after('discord_connected_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['discord_id']);
            $table->dropColumn([
                'discord_id',
                'discord_username',
                'discord_avatar',
                'discord_dm_channel_id',
                'discord_connected_at',
                'discord_dm_failed_reason',
            ]);
        });
    }
};
