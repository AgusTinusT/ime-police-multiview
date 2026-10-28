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
        Schema::create('bio_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_slug', 40)->unique();
            $table->foreignId('officer_bio_profile_id')->constrained('officer_bio_profiles')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bio_slug_redirects');
    }
};
