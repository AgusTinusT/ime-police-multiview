<?php

use App\Support\BioLinkPlatforms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('officer_bio_links', function (Blueprint $table) {
            $table->string('platform', 32)->nullable()->after('link_type');
            $table->index('platform', 'idx_platform');
        });

        // Backfill existing URL links from their host; recommendations keep null.
        DB::table('officer_bio_links')
            ->whereNull('platform')
            ->whereNotNull('url')
            ->orderBy('id')
            ->select(['id', 'url_host'])
            ->chunkById(500, function ($links) {
                foreach ($links as $link) {
                    DB::table('officer_bio_links')
                        ->where('id', $link->id)
                        ->update(['platform' => BioLinkPlatforms::detect($link->url_host)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('officer_bio_links', function (Blueprint $table) {
            $table->dropIndex('idx_platform');
            $table->dropColumn('platform');
        });
    }
};
