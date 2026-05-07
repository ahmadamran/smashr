<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('tournament_category_id')->nullable()->after('tournament_id')->constrained('tournament_categories')->nullOnDelete();
            $table->unsignedInteger('draw_round')->nullable()->after('winner_side');
            $table->string('draw_group')->nullable()->after('draw_round');
            $table->unsignedInteger('draw_position')->nullable()->after('draw_group');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tournament_category_id');
            $table->dropColumn(['draw_round', 'draw_group', 'draw_position']);
        });
    }
};
