<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * is_listed = false: the officer row only backs a bio-link (non-personnel
     * account). Hidden from every public roster, stream sync and leaderboard,
     * while the bio-link page keeps working as long as is_active stays true.
     */
    public function up(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->boolean('is_listed')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->dropColumn('is_listed');
        });
    }
};
