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
        Schema::create('squads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->string('slug')->unique();
            $table->string('description', 160)->nullable();
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->foreignId('forked_from_id')->nullable()->constrained('squads')->nullOnDelete();
            $table->unsignedInteger('forks_count')->default(0);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->index(['visibility', 'is_hidden']);
        });

        Schema::create('squad_officer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('squad_id')->constrained()->cascadeOnDelete();
            $table->foreignId('officer_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);

            $table->unique(['squad_id', 'officer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('squad_officer');
        Schema::dropIfExists('squads');
    }
};
