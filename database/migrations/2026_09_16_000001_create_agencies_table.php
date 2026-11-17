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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('agency_code', 10)->unique(); // e.g. SASP, LSPD, BCSO, SAPR
            $table->string('agency_name', 100); // e.g. San Andreas State Police
            $table->enum('jurisdiction', ['Statewide', 'City', 'County', 'State Parks'])->default('Statewide');
            $table->string('badge_logo_url', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
