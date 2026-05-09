<?php

namespace Modules\Tournaments\DrawEngine\Services;

class FeedRuleService
{
    public function poolWinnerRule(string $groupName, int $position = 1): string
    {
        return "Pool {$groupName} rank {$position}";
    }

    public function matchWinnerRule(int $matchPosition, string $stage = 'main'): string
    {
        return "Winner of {$stage} match {$matchPosition}";
    }

    public function doubleEliminationDropRule(int $matchPosition): string
    {
        return "Loser of winners match {$matchPosition}";
    }
}
