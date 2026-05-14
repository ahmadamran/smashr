<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            $table->decimal('mixed_rating', 4, 3)->default(3.500)->after('doubles_rating');
            $table->unsignedInteger('mixed_matches')->default(0)->after('doubles_matches');
        });
    }

    public function down(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            $table->dropColumn(['mixed_rating', 'mixed_matches']);
        });
    }
};
