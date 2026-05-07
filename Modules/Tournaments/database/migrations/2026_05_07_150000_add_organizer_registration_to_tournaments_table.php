<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->foreignId('organizer_id')->nullable()->after('club_id')->constrained('users')->nullOnDelete();
            $table->string('venue')->nullable()->after('city');
            $table->string('registration_mode')->default('public')->after('status');
            $table->string('registration_status')->default('open')->after('registration_mode');
            $table->date('registration_deadline')->nullable()->after('registration_status');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organizer_id');
            $table->dropColumn(['venue', 'registration_mode', 'registration_status', 'registration_deadline']);
        });
    }
};
