<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('group_size')->default(4)->after('draw_mode');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_categories', function (Blueprint $table) {
            $table->dropColumn('group_size');
        });
    }
};
