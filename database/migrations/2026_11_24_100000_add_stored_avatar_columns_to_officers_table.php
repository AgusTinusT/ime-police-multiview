<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Custom avatars set from a link are copied to object storage. avatar_url
     * then points at /media/avatars/{file}; avatar_source_url keeps the link
     * the photo was taken from.
     */
    public function up(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->string('avatar_disk', 32)->nullable()->after('avatar_url');
            $table->string('avatar_path')->nullable()->after('avatar_disk');
            $table->string('avatar_source_url', 500)->nullable()->after('avatar_path');
            $table->index('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->dropIndex(['avatar_path']);
            $table->dropColumn(['avatar_disk', 'avatar_path', 'avatar_source_url']);
        });
    }
};
