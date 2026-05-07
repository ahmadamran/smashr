<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('score_sheet_token', 16)->nullable()->unique()->after('draw_position');
            $table->string('live_status')->default('scheduled')->after('score_sheet_token');
            $table->json('live_score')->nullable()->after('live_status');
            $table->timestamp('score_submitted_at')->nullable()->after('live_score');
        });

        foreach (DB::table('matches')->whereNotNull('tournament_id')->cursor() as $match) {
            do {
                $token = Str::lower(Str::random(8));
            } while (DB::table('matches')->where('score_sheet_token', $token)->exists());

            DB::table('matches')
                ->where('id', $match->id)
                ->update([
                    'score_sheet_token' => $token,
                    'live_status' => empty(json_decode($match->score ?? '[]', true)) ? 'scheduled' : 'approved',
                    'live_score' => json_encode([
                        'current_game' => 1,
                        'current' => ['a' => 0, 'b' => 0],
                        'games' => json_decode($match->score ?? '[]', true) ?: [],
                        'history' => [],
                    ]),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropUnique(['score_sheet_token']);
            $table->dropColumn(['score_sheet_token', 'live_status', 'live_score', 'score_submitted_at']);
        });
    }
};
