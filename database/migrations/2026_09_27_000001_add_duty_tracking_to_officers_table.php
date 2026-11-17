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
            if (!Schema::hasColumn('officers', 'monthly_duty_minutes')) {
                $table->integer('monthly_duty_minutes')->default(0)->after('subscriber_count_text');
            }
            if (!Schema::hasColumn('officers', 'last_duty_at')) {
                $table->timestamp('last_duty_at')->nullable()->after('monthly_duty_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->dropColumn(['monthly_duty_minutes', 'last_duty_at']);
        });
    }
};
