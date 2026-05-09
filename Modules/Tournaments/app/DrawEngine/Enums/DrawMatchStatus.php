<?php

namespace Modules\Tournaments\DrawEngine\Enums;

enum DrawMatchStatus: string
{
    case Planned = 'planned';
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
}
