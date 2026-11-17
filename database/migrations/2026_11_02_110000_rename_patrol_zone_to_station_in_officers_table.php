<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->renameColumn('patrol_zone', 'station');
        });

        // Clean up or normalize any legacy patrol_zone values to null or valid stations
        DB::table('officers')
            ->whereNotNull('station')
            ->whereNotIn('station', ['71', '72', 'Central'])
            ->update(['station' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->renameColumn('station', 'patrol_zone');
        });
    }
};

