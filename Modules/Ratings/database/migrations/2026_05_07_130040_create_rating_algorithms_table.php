<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rating_algorithms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('version');
            $table->string('status')->default('draft');
            $table->json('settings');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->unique('version');
        });

        Schema::table('rating_events', function (Blueprint $table) {
            $table->foreignId('rating_algorithm_id')->nullable()->after('match_id')->constrained('rating_algorithms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rating_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rating_algorithm_id');
        });

        Schema::dropIfExists('rating_algorithms');
    }
};
