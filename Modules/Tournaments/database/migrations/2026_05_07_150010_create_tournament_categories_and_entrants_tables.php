<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('format')->default('singles');
            $table->string('level_label')->nullable();
            $table->string('draw_mode')->default('single_elimination');
            $table->unsignedInteger('max_entrants')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->unique(['tournament_id', 'slug']);
        });

        Schema::create('tournament_entrants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('seed')->nullable();
            $table->unsignedInteger('draw_position')->nullable();
            $table->string('group_name')->nullable();
            $table->timestamps();
        });

        Schema::create('tournament_entrant_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_entrant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name')->nullable();
            $table->unsignedTinyInteger('position')->default(1);
            $table->timestamps();
            $table->unique(['tournament_entrant_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_entrant_players');
        Schema::dropIfExists('tournament_entrants');
        Schema::dropIfExists('tournament_categories');
    }
};
