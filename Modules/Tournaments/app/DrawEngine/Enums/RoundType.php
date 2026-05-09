<?php

namespace Modules\Tournaments\DrawEngine\Enums;

enum RoundType: string
{
    case Pool = 'pool';
    case Round = 'round';
    case Quarterfinal = 'quarterfinal';
    case Semifinal = 'semifinal';
    case Final = 'final';
}
