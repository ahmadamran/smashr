<?php

namespace Modules\Tournaments\DrawEngine\Enums;

enum DrawType: string
{
    case SingleElimination = 'single_elimination';
    case DoubleElimination = 'double_elimination';
    case RoundRobin = 'round_robin';
    case PoolToKnockout = 'pool_to_knockout';

    public function label(): string
    {
        return match ($this) {
            self::SingleElimination => 'Single elimination',
            self::DoubleElimination => 'Double elimination',
            self::RoundRobin => 'Round robin',
            self::PoolToKnockout => 'Pool to knockout',
        };
    }
}
