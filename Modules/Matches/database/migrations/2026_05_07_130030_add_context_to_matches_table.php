<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('club_id')->nullable()->after('submitted_by')->constrained()->nullOnDelete();
            $table->foreignId('tournament_id')->nullable()->after('club_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tournament_id');
            $table->dropConstrainedForeignId('club_id');
        });
    }
};
