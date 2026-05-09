<?php

namespace Modules\Tournaments\DrawEngine\Enums;

enum ScheduleConstraint: string
{
    case DailyRoundLimit = 'daily_round_limit';
    case CourtAvailability = 'court_availability';
    case RestTime = 'rest_time';
    case PlayerConflict = 'player_conflict';
    case MaxMatchesPerPlayerPerDay = 'max_matches_per_player_per_day';
    case ManualOverride = 'manual_override';
}
