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
        Schema::create('clipper_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granted_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_user_id')->constrained('users')->onDelete('cascade');
            $table->string('tier', 20)->default('creator');
            $table->timestamps();

            $table->unique('assigned_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clipper_delegations');
    }
};
