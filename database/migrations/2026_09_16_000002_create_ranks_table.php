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
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('cascade');
            $table->string('rank_title', 50); // e.g. Trooper, Officer II, Deputy Sheriff, Captain
            $table->integer('level')->default(1); // Hierarchy level: 1 = Cadet, 2 = Trooper/Officer, 5 = Sergeant, 10 = Chief/Commissioner
            $table->decimal('base_salary', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranks');
    }
};
