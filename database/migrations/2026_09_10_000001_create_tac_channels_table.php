<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tac_channels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // 'TAC_1', 'TAC_2', 'TAC_3', 'TAC_4', 'TAC_5'
            $table->string('name', 50);           // 'TAC 1', 'TAC 2', 'TAC 3', 'TAC 4', 'TAC 5'
            $table->json('video_ids')->nullable(); // Array of active stream video IDs
            $table->timestamp('expires_at')->nullable(); // Auto-expire timer timestamp
            $table->timestamps();
        });

        // Seed initial 5 TAC channels
        $now = now();
        $channels = [
            ['code' => 'TAC_1', 'name' => 'TAC 1', 'video_ids' => json_encode([]), 'expires_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'TAC_2', 'name' => 'TAC 2', 'video_ids' => json_encode([]), 'expires_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'TAC_3', 'name' => 'TAC 3', 'video_ids' => json_encode([]), 'expires_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'TAC_4', 'name' => 'TAC 4', 'video_ids' => json_encode([]), 'expires_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'TAC_5', 'name' => 'TAC 5', 'video_ids' => json_encode([]), 'expires_at' => null, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('tac_channels')->insert($channels);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tac_channels');
    }
};
