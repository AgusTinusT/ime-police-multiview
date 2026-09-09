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
        Schema::create('officers', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id', 64)->unique();
            $table->string('handle', 100);
            $table->string('streamer_name', 150);
            $table->string('officer_name', 150);
            $table->string('callsign', 50)->index(); // e.g. 1-ADAM-12, 1-LINCOLN-1, AIR-1
            $table->string('badge_number', 50)->nullable(); // e.g. #402, #108
            $table->string('department', 50)->default('LSPD')->index(); // LSPD, BCSO, SASP, SWAT, AIR_SUPPORT, TRAFFIC, K9, DISPATCH
            $table->string('rank', 100)->default('Officer'); // Chief, Sheriff, Captain, Lieutenant, Sergeant, Senior Officer, Deputy, Cadet
            $table->string('patrol_zone', 150)->nullable(); // Mission Row, Sandy Shores, Paleto Bay, Vinewood, etc.
            $table->string('avatar_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officers');
    }
};
