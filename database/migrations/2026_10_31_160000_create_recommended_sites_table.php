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
        Schema::create('recommended_sites', function (Blueprint $t) {
            $t->id();
            $t->string('name', 60);
            $t->string('badge', 40)->nullable();
            $t->string('poster_name', 80);
            $t->string('poster_subtitle', 60)->nullable();
            $t->string('poster_image_url');
            $t->string('background_image_url')->nullable();
            $t->string('url');
            $t->string('cta_text', 40)->default('Kunjungi website');
            $t->text('description');
            $t->json('chips')->nullable();
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommended_sites');
    }
};

