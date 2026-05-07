<?php

namespace Modules\Matches\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Matches\Models\MatchRecord;

class MatchScoreService
{
    public function ensureScoreSheetToken(MatchRecord $match): string
    {
        if (filled($match->score_sheet_token)) {
            return $match->score_sheet_token;
        }

        do {
            $token = Str::lower(Str::random(8));
        } while (MatchRecord::where('score_sheet_token', $token)->exists());

        $match->forceFill(['score_sheet_token' => $token])->save();

        return $token;
    }

    public function initialLiveScore(): array
    {
        return [
            'current_game' => 1,
            'current' => ['a' => 0, 'b' => 0],
            'games' => [],
            'history' => [],
        ];
    }

    public function liveScore(MatchRecord $match): array
    {
        return array_replace_recursive($this->initialLiveScore(), $match->live_score ?? []);
    }

    public function addPoint(MatchRecord $match, string $side): void
    {
        $this->guardEditable($match);

        $side = strtoupper($side);
        abort_unless(in_array($side, ['A', 'B'], true), 422);

        $liveScore = $this->liveScore($match);
        $key = strtolower($side);
        $liveScore['current'][$key] = min(30, ((int) ($liveScore['current'][$key] ?? 0)) + 1);
        $liveScore['history'][] = [
            'type' => 'point',
            'side' => $side,
            'game' => (int) $liveScore['current_game'],
        ];

        $match->forceFill([
            'live_status' => 'live',
            'live_score' => $liveScore,
        ])->save();
    }

    public function undoPoint(MatchRecord $match): void
    {
        $this->guardEditable($match);

        $liveScore = $this->liveScore($match);
        $last = array_pop($liveScore['history']);

        if (($last['type'] ?? null) === 'point') {
            $key = strtolower($last['side']);
            $liveScore['current'][$key] = max(0, ((int) ($liveScore['current'][$key] ?? 0)) - 1);
        }

        $match->forceFill([
            'live_status' => empty($liveScore['games']) && ($liveScore['current']['a'] ?? 0) === 0 && ($liveScore['current']['b'] ?? 0) === 0 ? 'scheduled' : 'live',
            'live_score' => $liveScore,
        ])->save();
    }

    public function endCurrentGame(MatchRecord $match): void
    {
        $this->guardEditable($match);

        $liveScore = $this->liveScore($match);
        $current = [
            'a' => (int) ($liveScore['current']['a'] ?? 0),
            'b' => (int) ($liveScore['current']['b'] ?? 0),
        ];

        if (! $this->isCompletedGame($current)) {
            throw ValidationException::withMessages([
                'score' => 'Current game is not finished yet.',
            ]);
        }

        $liveScore['games'][] = $current;
        $liveScore['current_game'] = count($liveScore['games']) + 1;
        $liveScore['current'] = ['a' => 0, 'b' => 0];
        $liveScore['history'] = [];

        $match->forceFill([
            'live_status' => 'live',
            'live_score' => $liveScore,
        ])->save();
    }

    public function submitLiveScore(MatchRecord $match): array
    {
        $this->guardEditable($match);

        $liveScore = $this->liveScore($match);
        $games = $liveScore['games'] ?? [];
        $current = [
            'a' => (int) ($liveScore['current']['a'] ?? 0),
            'b' => (int) ($liveScore['current']['b'] ?? 0),
        ];

        if ($this->isCompletedGame($current)) {
            $games[] = $current;
            $liveScore['games'] = $games;
            $liveScore['current'] = ['a' => 0, 'b' => 0];
            $liveScore['current_game'] = count($games) + 1;
            $liveScore['history'] = [];
        }

        $result = $this->validateCompletedMatch($games);

        $match->forceFill([
            'live_status' => 'submitted',
            'live_score' => $liveScore,
            'status' => 'pending_confirmation',
            'score_submitted_at' => now(),
        ])->save();

        return $result;
    }

    public function validateScoreRows(array $games, ?string $winnerSide = null): array
    {
        $score = collect($games)
            ->filter(fn (array $game) => filled($game['a'] ?? null) && filled($game['b'] ?? null))
            ->map(fn (array $game) => ['a' => (int) $game['a'], 'b' => (int) $game['b']])
            ->values()
            ->all();

        return $this->validateCompletedMatch($score, $winnerSide);
    }

    public function approveSubmittedScore(MatchRecord $match): array
    {
        $result = $this->validateCompletedMatch($match->live_score['games'] ?? []);

        $match->forceFill([
            'score' => $result['score'],
            'winner_side' => $result['winner_side'],
            'live_status' => 'approved',
        ])->save();

        return $result;
    }

    public function isCompletedGame(array $game): bool
    {
        $a = (int) ($game['a'] ?? 0);
        $b = (int) ($game['b'] ?? 0);
        $winner = max($a, $b);
        $loser = min($a, $b);

        return ($winner === 30 && $winner > $loser) || ($winner >= 21 && ($winner - $loser) >= 2);
    }

    private function validateCompletedMatch(array $score, ?string $winnerSide = null): array
    {
        if (empty($score)) {
            throw ValidationException::withMessages([
                'score' => 'Enter at least one completed game score.',
            ]);
        }

        if (count($score) > 3) {
            throw ValidationException::withMessages([
                'score' => 'A badminton match can only have up to three games.',
            ]);
        }

        $sideAWins = 0;
        $sideBWins = 0;

        foreach ($score as $game) {
            if (! $this->isCompletedGame($game)) {
                throw ValidationException::withMessages([
                    'score' => 'Each submitted game must be complete.',
                ]);
            }

            ((int) $game['a'] > (int) $game['b']) ? $sideAWins++ : $sideBWins++;
        }

        $actualWinner = $sideAWins > $sideBWins ? 'A' : 'B';

        if ($sideAWins === $sideBWins || max($sideAWins, $sideBWins) < 2) {
            throw ValidationException::withMessages([
                'score' => 'Submit a best-of-three result with one side winning two games.',
            ]);
        }

        if ($winnerSide && $winnerSide !== $actualWinner) {
            throw ValidationException::withMessages([
                'result' => 'Winner side must match the submitted game scores.',
            ]);
        }

        return [
            'score' => array_values($score),
            'winner_side' => $actualWinner,
        ];
    }

    private function guardEditable(MatchRecord $match): void
    {
        if ($match->status === 'confirmed' || in_array($match->live_status, ['submitted', 'approved'], true)) {
            throw ValidationException::withMessages([
                'score' => 'This score sheet has already been submitted.',
            ]);
        }
    }
}
