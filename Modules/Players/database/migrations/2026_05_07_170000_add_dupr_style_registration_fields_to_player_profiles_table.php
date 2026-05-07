<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->after('slug');
            $table->string('gender')->nullable()->after('phone_number');
            $table->date('birthdate')->nullable()->after('gender');
            $table->string('postal_code')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'gender', 'birthdate', 'postal_code']);
        });
    }
};
