<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            $table->integer('smashr_points')->default(0)->after('primary_format');
        });

        Schema::create('smashr_point_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('before_points');
            $table->integer('adjustment');
            $table->integer('after_points');
            $table->string('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smashr_point_adjustments');

        Schema::table('player_profiles', function (Blueprint $table) {
            $table->dropColumn('smashr_points');
        });
    }
};
