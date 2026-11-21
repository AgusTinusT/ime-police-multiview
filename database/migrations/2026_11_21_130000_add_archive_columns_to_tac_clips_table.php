<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Momen Aksi archive: a 720p copy of each moment cut from the YouTube VOD and
     * stored on object storage for config('momen.archive_days') days. The row
     * itself (title, start/end seconds, officer, likes) is never deleted.
     *
     * archive_status: null (never queued) | pending | processing | stored | failed | expired
     */
    public function up(): void
    {
        Schema::table('tac_clips', function (Blueprint $table) {
            $table->string('archive_status', 16)->nullable()->after('is_approved')->index();
            $table->string('archive_disk', 32)->nullable()->after('archive_status');
            $table->string('archive_path')->nullable()->after('archive_disk');
            $table->unsignedBigInteger('archive_size')->nullable()->after('archive_path');
            $table->unsignedTinyInteger('archive_attempts')->default(0)->after('archive_size');
            $table->string('archive_error', 500)->nullable()->after('archive_attempts');
            $table->timestamp('archived_at')->nullable()->after('archive_error');
        });
    }

    public function down(): void
    {
        Schema::table('tac_clips', function (Blueprint $table) {
            $table->dropIndex(['archive_status']);
            $table->dropColumn([
                'archive_status', 'archive_disk', 'archive_path', 'archive_size',
                'archive_attempts', 'archive_error', 'archived_at',
            ]);
        });
    }
};
