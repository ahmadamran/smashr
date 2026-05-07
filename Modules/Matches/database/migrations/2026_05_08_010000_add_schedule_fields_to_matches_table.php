<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('played_at');
            $table->string('court_label')->nullable()->after('scheduled_at');
            $table->unsignedSmallInteger('estimated_duration_minutes')->nullable()->after('court_label');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'court_label', 'estimated_duration_minutes']);
        });
    }
};
