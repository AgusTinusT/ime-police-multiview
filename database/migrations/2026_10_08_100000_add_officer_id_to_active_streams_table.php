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
        Schema::table('active_streams', function (Blueprint $table) {
            $table->foreignId('officer_id')
                ->nullable()
                ->after('id')
                ->constrained('officers')
                ->onDelete('cascade');
            
            $table->index(['officer_id', 'status']);
        });

        // Backfill officer_id for existing active_streams where channel_id matches officers.channel_id
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("
                UPDATE active_streams s
                JOIN officers o ON s.channel_id = o.channel_id
                SET s.officer_id = o.id
                WHERE s.officer_id IS NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('active_streams', function (Blueprint $table) {
            $table->dropForeign(['officer_id']);
            $table->dropColumn('officer_id');
        });
    }
};
