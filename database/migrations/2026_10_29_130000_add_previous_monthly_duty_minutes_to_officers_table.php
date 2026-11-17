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
            if (!Schema::hasColumn('officers', 'previous_monthly_duty_minutes')) {
                $table->unsignedInteger('previous_monthly_duty_minutes')->default(0)->after('monthly_duty_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            if (Schema::hasColumn('officers', 'previous_monthly_duty_minutes')) {
                $table->dropColumn('previous_monthly_duty_minutes');
            }
        });
    }
};
