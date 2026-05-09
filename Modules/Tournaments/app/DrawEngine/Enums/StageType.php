<?php

namespace Modules\Tournaments\DrawEngine\Enums;

enum StageType: string
{
    case Main = 'main';
    case Winners = 'winners';
    case Losers = 'losers';
    case Pool = 'pool';
    case Knockout = 'knockout';
}
