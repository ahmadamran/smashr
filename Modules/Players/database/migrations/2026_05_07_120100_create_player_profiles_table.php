<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('slug')->unique();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('preferred_hand')->default('right');
            $table->string('primary_format')->default('doubles');
            $table->decimal('singles_rating', 4, 3)->default(3.500);
            $table->decimal('doubles_rating', 4, 3)->default(3.500);
            $table->unsignedInteger('singles_matches')->default(0);
            $table->unsignedInteger('doubles_matches')->default(0);
            $table->timestamps();
        });

        Schema::create('club_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['club_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_player');
        Schema::dropIfExists('player_profiles');
    }
};
